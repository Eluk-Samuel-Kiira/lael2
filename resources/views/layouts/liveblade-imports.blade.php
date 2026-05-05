<!-- important imports for laravel liveblades -->
<script src="{{ asset('blade-live/forms/forms.min.js') }}" type="module"></script>



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

<script>
    // Function to show the loader
    function showLoader() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'block';
        }
    }

    // Function to hide the loader
    function hideLoader() {
        const loader = document.getElementById('loader');
        if (loader) {
            loader.style.display = 'none';
        }
    }



    // Function to handle navigation
    function navigateToGuestPage(url) {
        showLoader(); // Show loader during content loading

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text();
            })
            .then(data => {
                history.pushState({ url: url }, null, url); // Store state in history

                // Extract title and update page content
                const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                document.title = titleMatch ? titleMatch[1] : 'Default Title';
                document.getElementById('kt_app_root').innerHTML = data;

                if (typeof window.LiveBladeRefresh === 'function') {
                    window.LiveBladeRefresh();
                }
                setTimeout(hideLoader, 300);
            })
            .catch(error => {
                console.error('Error fetching content:', error);
                document.getElementById('kt_app_root').innerHTML = '404 Page Not Found.';
                hideLoader();
            });
    }


    // Handle back/forward navigation
    window.addEventListener('popstate', (event) => {
        if (event.state && event.state.url) {
            renderGuestPage(event.state.url); // Load the correct content
        } else {
            renderGuestPage(window.location.pathname); // Default behavior
        }
    });


    // Function to load content based on URL
    function renderGuestPage(url) {
        const pageContent = document.getElementById('kt_app_root');

        if (!pageContent) {
            // console.error('Error: Element #kt_app_root not found.');
            return; // Stop execution if the element is missing
        }

        showLoader(); // Show loader during content loading

        fetch(url)
            .then(response => {
                if (!response.ok) {
                    throw new Error('Network response was not ok');
                }
                return response.text(); // Get the HTML content
            })
            .then(data => {
                // Extract the title from the fetched content
                const titleMatch = data.match(/<title>(.*?)<\/title>/i);
                document.title = titleMatch ? titleMatch[1] : 'Default Title'; // Update the document title

                // Insert the fetched content into the page
                pageContent.innerHTML = data;

                // Hide the loader after a small delay (optional)
                setTimeout(hideLoader, 300);
            })
            .catch(error => {
                console.error('Error fetching content:', error);
                
                if (pageContent) {
                    pageContent.innerHTML = '404 Page Not Found.'; // Fallback content
                }

                hideLoader();
            });
    }


    // Reload apge especiall when going to the database
    function reloadTo(url) {
        window.location.href = url; // Redirect on success
    }

    // Initial load
    renderGuestPage(window.location.pathname);
</script>


<!-- Guest Js Defind functions  -->
@include('layouts.guest-js')

<!-- App/Dashboard Js Defind functions  -->
@include('layouts.app-js')
@include('layouts.procurement-js')
@include('layouts.pos-js')




<script>
    function handleFormSubmiter(formData, submitButtonId) {
        
        // console.log(formData);

        // This is the loader
        LiveBlade.toggleButtonLoading(submitButtonId, true);
        
        LiveBlade.submitFormItems(formData)
        .then(noErrors => {
            console.log(noErrors);
            
            // Only for Login 
            if (formData["routeName"] === "/login") {
                if (noErrors) {
                    // Success toast notification
                    toastr.success(
                        '{{ __("auth.login_success_message") }}', 
                        '{{ __("auth.login_success_title") }}', 
                        {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-top-right",
                            timeOut: 3000,
                            extendedTimeOut: 1000,
                            showMethod: 'fadeIn',
                            hideMethod: 'fadeOut',
                            onclick: null
                        }
                    );
                    
                    // Redirect after a short delay
                    setTimeout(() => {
                        reloadTo('/dashboard');
                    }, 1500);
                } else {
                    // Error toast notification
                    toastr.error(
                        '{{ __("auth.login_error_message") }}', 
                        '{{ __("auth.login_error_title") }}', 
                        {
                            closeButton: true,
                            progressBar: true,
                            positionClass: "toast-top-right",
                            timeOut: 5000,
                            extendedTimeOut: 2000,
                            showMethod: 'fadeIn',
                            hideMethod: 'fadeOut',
                            onclick: null
                        }
                    );
                }
            }

        })
        .catch(error => {
            console.error('An unexpected error occurred:', error);
            // Error toast for unexpected errors
            toastr.error(
                '{{ __("auth.unexpected_error") }}', 
                '{{ __("auth.error_title") }}', 
                {
                    closeButton: true,
                    progressBar: true,
                    positionClass: "toast-top-right",
                    timeOut: 5000,
                    extendedTimeOut: 2000
                }
            );
        })
        .finally(() => {
            LiveBlade.toggleButtonLoading(submitButtonId, false);
        });

    }
</script>




<style>
    #lb-page-loader {
        display:none; position:fixed; top:0; left:0;
        width:100%; height:3px; z-index:10000;
        background:var(--bs-primary,#0d6efd);
        animation:lb-slide 1.4s ease-in-out infinite;
    }
    @keyframes lb-slide {
        0%  { width:0;    opacity:1; }
        70% { width:85%;  opacity:1; }
        100%{ width:100%; opacity:0; }
    }
</style>
<div id="lb-page-loader"></div>

<script>
    (function () {
        'use strict';

        function showLoader(){ document.getElementById('lb-page-loader').style.display='block'; }
        function hideLoader(){ setTimeout(()=>{ document.getElementById('lb-page-loader').style.display='none'; },300); }

        function getCsrf(){
            return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
                || document.querySelector('input[name="_token"]')?.value || '';
        }

        function debounce(fn, ms){ let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a),ms); }; }

        // ── Load a blade component partial into its container ─────────────────────
        function loadComponent(baseUrl, componentId, extraParams = {}) {
            const container = document.getElementById(componentId);
            if (!container) return;

            const url = new URL(baseUrl, window.location.origin);
            url.searchParams.set('bladeFileToReload', componentId);
            Object.entries(extraParams).forEach(([k,v]) => url.searchParams.set(k, v));

            showLoader();
            fetch(url.toString(), {
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': getCsrf(), 'X-Requested-With': 'XMLHttpRequest' },
            })
            .then(r => { if (!r.ok) throw new Error(`HTTP ${r.status}`); return r.text(); })
            .then(html => {
                const doc   = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById(componentId);
                container.innerHTML = fresh ? fresh.innerHTML : html;
                // Re-initialize after content update
                if (typeof window.LiveBladeRefresh === 'function') {
                    window.LiveBladeRefresh();
                }
            })
            .catch(err => console.error('[LiveBlade] loadComponent:', err))
            .finally(hideLoader);
        }

        // ═══════════════════════════════════════════════════════════════════════════
        // LiveBladeRefresh — Global function to re-initialize everything
        // Call this after ANY content change (SPA navigation, AJAX updates)
        // ═══════════════════════════════════════════════════════════════════════════
        window.LiveBladeRefresh = function() {
            // console.log('Yes we are fully working');
            
            // 1. Search inputs
            document.querySelectorAll('.lb-search-input:not([data-lb-initialized])').forEach(input => {
                input.setAttribute('data-lb-initialized', '1');

                const componentId = input.dataset.lbComponent;
                const baseUrl     = input.dataset.lbUrl || window.location.pathname;
                const debounceMs  = parseInt(input.dataset.lbDebounce, 10) || 380;
                const minChars    = parseInt(input.dataset.lbMinChars,  10) || 0;

                const doSearch = debounce(function () {
                    const q = input.value.trim();
                    if (q.length > 0 && q.length < minChars) return;

                    const paginationWrap = document.querySelector('[data-lb-pagination]');
                    const perPage = paginationWrap?.querySelector('.lb-per-page-select')?.value
                                || paginationWrap?.dataset.lbPerPage
                                || 15;

                    const params = { page: 1, per_page: perPage };
                    if (q) params.search = q;

                    loadComponent(baseUrl, componentId, params);
                }, debounceMs);

                input.addEventListener('input',  doSearch);
                input.addEventListener('search', doSearch);
            });

            // 2. Pagination wrappers
            document.querySelectorAll('[data-lb-pagination]:not([data-lb-initialized])').forEach(wrap => {
                wrap.setAttribute('data-lb-initialized', '1');

                const baseUrl       = wrap.dataset.lbUrl || window.location.pathname;
                const searchInputId = wrap.dataset.lbSearchInput;

                function getPerPage() {
                    return wrap.querySelector('.lb-per-page-select')?.value
                        || wrap.dataset.lbPerPage || 15;
                }

                function getSearch() {
                    if (searchInputId) {
                        return document.getElementById(searchInputId)?.value?.trim() || '';
                    }
                    return document.querySelector('.lb-search-input')?.value?.trim() || '';
                }

                function deriveComponentId() {
                    if (wrap.dataset.lbComponent) return wrap.dataset.lbComponent;
                    const pid = wrap.id || '';
                    if (pid) return pid.replace(/Pagination$/i, 'Component');
                    const sibling = wrap.closest('.card')?.querySelector('[id$="Component"]');
                    return sibling?.id || '';
                }

                function goToPage(page) {
                    const componentId = deriveComponentId();
                    if (!componentId) {
                        console.warn('[LiveBlade] Cannot derive componentId from:', wrap.id);
                        return;
                    }
                    const params = { page, per_page: getPerPage() };
                    const q = getSearch();
                    if (q) params.search = q;
                    loadComponent(baseUrl, componentId, params);
                }

                wrap.addEventListener('click', function (e) {
                    const btn = e.target.closest('.lb-page-btn');
                    if (!btn || btn.disabled) return;
                    e.preventDefault();
                    e.stopPropagation();
                    goToPage(parseInt(btn.dataset.page, 10));
                });

                const sel = wrap.querySelector('.lb-per-page-select');
                if (sel) sel.addEventListener('change', () => goToPage(1));
            });

            // 3. FILTER SELECTS with Searchable Support
            function initFilters() {
                // Store original options for dependent filters
                const originalOptions = new Map();
                let debounceTimer;
                
                // Handle both searchable inputs and regular selects
                document.querySelectorAll('.lb-filter-input, .lb-filter-select').forEach(element => {
                    if (element.classList.contains('lb-filter-input')) {
                        initSearchableFilter(element);
                    } else {
                        initRegularFilter(element);
                    }
                });
                
                function initSearchableFilter(input) {
                    if (input.getAttribute('data-lb-initialized')) return;
                    input.setAttribute('data-lb-initialized', '1');
                    
                    const componentId = input.dataset.lbComponent;
                    const baseUrl = input.dataset.lbUrl || window.location.pathname;
                    const filterName = input.dataset.lbFilter;
                    const dependsOn = input.dataset.lbDependsOn;
                    const hiddenInput = document.getElementById(input.id.replace('_input', ''));
                    const datalistId = input.getAttribute('list');
                    const datalist = document.getElementById(datalistId);
                    
                    if (!hiddenInput) return;
                    
                    // Store original options for dependent filtering
                    if (datalist) {
                        const options = [];
                        datalist.querySelectorAll('option').forEach(opt => {
                            if (opt.value && !opt.value.includes('- All')) {
                                options.push({
                                    value: opt.getAttribute('data-value'),
                                    text: opt.value,
                                    parentId: opt.getAttribute('data-parent-id') || ''
                                });
                            }
                        });
                        originalOptions.set(datalistId, options);
                    }
                    
                    // Function to filter dependent options
                    function filterDependentOptions() {
                        if (!dependsOn) return;
                        
                        const parentSelect = document.querySelector(`.lb-filter-input[data-lb-filter="${dependsOn}"], .lb-filter-select[data-lb-filter="${dependsOn}"]`);
                        if (!parentSelect) return;
                        
                        let parentValue;
                        if (parentSelect.classList.contains('lb-filter-input')) {
                            const parentHidden = document.getElementById(parentSelect.id.replace('_input', ''));
                            parentValue = parentHidden?.value;
                        } else {
                            parentValue = parentSelect.value;
                        }
                        
                        const originalOpts = originalOptions.get(datalistId) || [];
                        
                        // Clear current datalist (keep the "All" option)
                        datalist.innerHTML = '';
                        
                        // Add "All" option first
                        const allOption = document.createElement('option');
                        allOption.value = `${filterName} - All`;
                        allOption.setAttribute('data-value', '');
                        datalist.appendChild(allOption);
                        
                        // Filter options based on parent value
                        let filteredOpts = originalOpts;
                        if (parentValue) {
                            filteredOpts = originalOpts.filter(opt => opt.parentId == parentValue);
                        }
                        
                        // Add filtered options
                        filteredOpts.forEach(opt => {
                            const option = document.createElement('option');
                            option.value = opt.text;
                            option.setAttribute('data-value', opt.value);
                            option.setAttribute('data-parent-id', opt.parentId);
                            datalist.appendChild(option);
                        });
                    }
                    
                    // Sync hidden input with visible input
                    function syncHiddenValue() {
                        const inputValue = input.value.trim();
                        let matchedValue = '';
                        let matchedText = inputValue;
                        
                        if (datalist && inputValue) {
                            const options = datalist.querySelectorAll('option');
                            for (let i = 0; i < options.length; i++) {
                                const optionValue = options[i].value;
                                if (optionValue === inputValue) {
                                    matchedValue = options[i].getAttribute('data-value');
                                    matchedText = optionValue;
                                    break;
                                }
                            }
                        }
                        
                        hiddenInput.value = matchedValue;
                        return { value: matchedValue, text: matchedText };
                    }
                    
                    // Trigger filter change (with debounce)
                    let triggerTimeout;
                    function triggerFilter() {
                        clearTimeout(triggerTimeout);
                        triggerTimeout = setTimeout(() => {
                            const { value: filterValue } = syncHiddenValue();
                            
                            // Get current search term
                            const searchInput = document.querySelector(`.lb-search-input[data-lb-component="${componentId}"]`);
                            const searchTerm = searchInput?.value?.trim() || '';
                            
                            // Get current pagination per_page
                            const paginationWrap = document.querySelector(`[data-lb-pagination][data-lb-component="${componentId}"]`);
                            const perPage = paginationWrap?.querySelector('.lb-per-page-select')?.value || 15;
                            
                            // Build params
                            const params = { page: 1, per_page: perPage };
                            if (searchTerm) params.search = searchTerm;
                            
                            // Collect all active filters
                            document.querySelectorAll(`.lb-filter-input[data-lb-component="${componentId}"], .lb-filter-select[data-lb-component="${componentId}"]`).forEach(otherFilter => {
                                let otherValue;
                                if (otherFilter.classList.contains('lb-filter-input')) {
                                    const otherHidden = document.getElementById(otherFilter.id.replace('_input', ''));
                                    otherValue = otherHidden?.value;
                                } else {
                                    otherValue = otherFilter.value;
                                }
                                const otherFilterName = otherFilter.dataset.lbFilter;
                                if (otherValue && otherFilterName && otherFilterName !== filterName) {
                                    params[otherFilterName] = otherValue;
                                }
                            });
                            
                            // Add current filter
                            if (filterValue) {
                                params[filterName] = filterValue;
                            }
                            
                            loadComponent(baseUrl, componentId, params);
                        }, 300);
                    }
                    
                    // Handle input changes (typing)
                    input.addEventListener('input', function(e) {
                        e.stopPropagation();
                        const { value: matchedValue, text: matchedText } = syncHiddenValue();
                        
                        // Update dependent filters if needed
                        if (dependsOn) {
                            filterDependentOptions();
                        }
                        
                        // Trigger filter after debounce
                        triggerFilter();
                    });
                    
                    // Handle selection from datalist (clicking an option)
                    input.addEventListener('change', function() {
                        syncHiddenValue();
                        if (dependsOn) filterDependentOptions();
                        triggerFilter();
                    });
                    
                    // Handle blur (when user leaves the field)
                    input.addEventListener('blur', function() {
                        syncHiddenValue();
                    });
                    
                    // Initial sync and filter
                    syncHiddenValue();
                    if (dependsOn) filterDependentOptions();
                    
                    // If there's an initial value that matches, trigger filter
                    if (hiddenInput.value) {
                        setTimeout(() => triggerFilter(), 100);
                    }
                    
                    // Listen for parent changes
                    if (dependsOn) {
                        const parentElement = document.querySelector(`.lb-filter-input[data-lb-filter="${dependsOn}"], .lb-filter-select[data-lb-filter="${dependsOn}"]`);
                        if (parentElement) {
                            const parentHandler = function() {
                                filterDependentOptions();
                                // Clear current input value if it no longer matches
                                if (input.value) {
                                    syncHiddenValue();
                                    if (!hiddenInput.value) {
                                        input.value = '';
                                    }
                                }
                            };
                            parentElement.addEventListener('change', parentHandler);
                            if (parentElement.classList.contains('lb-filter-input')) {
                                parentElement.addEventListener('input', parentHandler);
                            }
                        }
                    }
                }
                
                function initRegularFilter(select) {
                    if (select.getAttribute('data-lb-initialized')) return;
                    select.setAttribute('data-lb-initialized', '1');
                    
                    const componentId = select.dataset.lbComponent;
                    const baseUrl = select.dataset.lbUrl || window.location.pathname;
                    
                    select.addEventListener('change', function() {
                        const filterValue = this.value;
                        const filterName = this.dataset.lbFilter;
                        
                        const searchInput = document.querySelector(`.lb-search-input[data-lb-component="${componentId}"]`);
                        const searchTerm = searchInput?.value?.trim() || '';
                        
                        const paginationWrap = document.querySelector(`[data-lb-pagination][data-lb-component="${componentId}"]`);
                        const perPage = paginationWrap?.querySelector('.lb-per-page-select')?.value || 15;
                        
                        const params = { page: 1, per_page: perPage };
                        if (searchTerm) params.search = searchTerm;
                        
                        document.querySelectorAll(`.lb-filter-input[data-lb-component="${componentId}"], .lb-filter-select[data-lb-component="${componentId}"]`).forEach(otherFilter => {
                            let otherValue;
                            if (otherFilter.classList.contains('lb-filter-input')) {
                                const otherHidden = document.getElementById(otherFilter.id.replace('_input', ''));
                                otherValue = otherHidden?.value;
                            } else {
                                otherValue = otherFilter.value;
                            }
                            const otherFilterName = otherFilter.dataset.lbFilter;
                            if (otherValue && otherFilterName) {
                                params[otherFilterName] = otherValue;
                            }
                        });
                        
                        loadComponent(baseUrl, componentId, params);
                    });
                    
                    // Initial load if has value
                    if (select.value) {
                        setTimeout(() => {
                            const event = new Event('change', { bubbles: true });
                            select.dispatchEvent(event);
                        }, 100);
                    }
                }
            }

            // Call initFilters inside LiveBladeRefresh
            initFilters();

            // 4. DEPENDENT DROPDOWNS with Typable Inputs
            document.querySelectorAll('.lb-dep-parent:not([data-lb-initialized])').forEach(parent => {
                parent.setAttribute('data-lb-initialized', '1');
                
                const parentInput = document.getElementById(parent.id + '_input');
                const childId = parent.dataset.lbChild;
                const childHidden = document.getElementById(childId);
                const childInput = document.getElementById(childId + '_input');
                const childDatalist = document.getElementById(childId + '_list');
                const route = parent.dataset.lbRoute;
                const loadingId = parent.dataset.lbLoading;
                const loadingSpinner = document.getElementById(loadingId);
                
                if (!childInput || !childHidden) return;
                
                // Store current options for filtering
                let currentChildOptions = [];
                
                function loadChildOptions(parentValue) {
                    if (!parentValue) {
                        childInput.value = '';
                        childHidden.value = '';
                        childInput.disabled = true;
                        childHidden.disabled = true;
                        childDatalist.innerHTML = '';
                        currentChildOptions = [];
                        return;
                    }
                    
                    if (loadingSpinner) loadingSpinner.style.display = 'block';
                    childInput.disabled = true;
                    childInput.placeholder = 'Loading...';
                    
                    const fetchUrl = new URL(route, window.location.origin);
                    fetchUrl.searchParams.set('parent_id', parentValue);
                    
                    fetch(fetchUrl.toString(), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(res => res.json())
                    .then(data => {
                        let options = '';
                        currentChildOptions = [];
                        
                        if (data.success && data.data && data.data.length > 0) {
                            data.data.forEach(item => {
                                const value = item.id || item.value;
                                const label = item.name || item.label;
                                options += `<option value="${label}" data-id="${value}"></option>`;
                                currentChildOptions.push({ id: value, name: label });
                            });
                            childInput.disabled = false;
                            childHidden.disabled = false;
                            childInput.placeholder = 'Type or select ' + childInput.getAttribute('data-placeholder') + '...';
                        } else {
                            options = '<option value="">No options available</option>';
                            childInput.disabled = true;
                            childHidden.disabled = true;
                        }
                        
                        childDatalist.innerHTML = options;
                    })
                    .catch(error => {
                        console.error('Dependent dropdown error:', error);
                        childDatalist.innerHTML = '<option value="">Error loading options</option>';
                        childInput.disabled = true;
                        childHidden.disabled = true;
                    })
                    .finally(() => {
                        if (loadingSpinner) loadingSpinner.style.display = 'none';
                    });
                }
                
                // Sync parent hidden input with text input
                function syncParentHidden() {
                    const inputValue = parentInput.value.trim();
                    const datalist = document.getElementById(parentInput.getAttribute('list'));
                    let matchedId = '';
                    
                    if (datalist && inputValue) {
                        const options = datalist.options;
                        for (let i = 0; i < options.length; i++) {
                            const optionValue = options[i].value;
                            if (optionValue === inputValue) {
                                matchedId = options[i].getAttribute('data-id');
                                break;
                            }
                        }
                    }
                    
                    parent.value = matchedId;
                    
                    if (matchedId) {
                        loadChildOptions(matchedId);
                    } else if (!inputValue) {
                        loadChildOptions('');
                    }
                }
                
                // Sync child hidden input with text input
                function syncChildHidden() {
                    const inputValue = childInput.value.trim();
                    let matchedId = '';
                    
                    if (inputValue && currentChildOptions.length > 0) {
                        const matched = currentChildOptions.find(opt => opt.name === inputValue);
                        if (matched) {
                            matchedId = matched.id;
                        }
                    }
                    
                    childHidden.value = matchedId;
                }
                
                // Handle parent input - prevent backspace issues
                parentInput.addEventListener('keydown', function(e) {
                    // Allow normal backspace behavior
                    e.stopPropagation();
                });
                
                parentInput.addEventListener('input', function(e) {
                    // Use requestAnimationFrame to avoid freezing
                    requestAnimationFrame(() => {
                        syncParentHidden();
                    });
                });
                
                parentInput.addEventListener('change', function() {
                    syncParentHidden();
                });
                
                // Handle child input - searchable/filterable
                childInput.addEventListener('keydown', function(e) {
                    e.stopPropagation();
                });
                
                childInput.addEventListener('input', function(e) {
                    requestAnimationFrame(() => {
                        syncChildHidden();
                        
                        // Filter datalist options based on input
                        const inputValue = this.value.trim().toLowerCase();
                        if (childDatalist && inputValue && currentChildOptions.length > 0) {
                            const filtered = currentChildOptions.filter(opt => 
                                opt.name.toLowerCase().includes(inputValue)
                            );
                            let options = '';
                            filtered.forEach(item => {
                                options += `<option value="${item.name}" data-id="${item.id}"></option>`;
                            });
                            childDatalist.innerHTML = options;
                        } else if (childDatalist && currentChildOptions.length > 0) {
                            // Reset to all options
                            let options = '';
                            currentChildOptions.forEach(item => {
                                options += `<option value="${item.name}" data-id="${item.id}"></option>`;
                            });
                            childDatalist.innerHTML = options;
                        }
                    });
                });
                
                childInput.addEventListener('change', function() {
                    syncChildHidden();
                });
                
                // Initial sync
                if (parentInput.value) {
                    syncParentHidden();
                }
            });

            // 5. TYPABLE SELECTS (Datalist - No Select2)
            // Initialize all typable select inputs
            document.querySelectorAll('.typable-select-input:not([data-lb-initialized])').forEach(input => {
                input.setAttribute('data-lb-initialized', '1');
                
                const hiddenId = input.getAttribute('data-hidden-id');
                const hidden = document.getElementById(hiddenId);
                const listId = input.getAttribute('data-list-id');
                const datalist = document.getElementById(listId);
                
                if (!hidden) return;
                
                function syncTypableValue() {
                    const inputValue = input.value.trim();
                    let matchedId = '';
                    
                    if (datalist && inputValue) {
                        const options = datalist.querySelectorAll('option');
                        for (let i = 0; i < options.length; i++) {
                            if (options[i].value === inputValue) {
                                matchedId = options[i].getAttribute('data-id');
                                break;
                            }
                        }
                    }
                    
                    hidden.value = matchedId;
                    
                    // Trigger change event on hidden input for any listeners
                    const changeEvent = new Event('change', { bubbles: true });
                    hidden.dispatchEvent(changeEvent);
                }
                
                // Remove old listeners to avoid duplicates
                input.removeEventListener('input', syncTypableValue);
                input.removeEventListener('change', syncTypableValue);
                
                // Add new listeners
                input.addEventListener('input', syncTypableValue);
                input.addEventListener('change', syncTypableValue);
                
                // Initial sync
                syncTypableValue();
                
                // Add focus styling
                input.addEventListener('focus', function() {
                    this.classList.add('border-primary');
                });
                
                input.addEventListener('blur', function() {
                    this.classList.remove('border-primary');
                    syncTypableValue();
                });
            });

        };

        // ── SPA navigation ────────────────────────────────────────────────────────
        let isNavigating = false;
        let currentUrl = window.location.pathname;

        function navigate(url, pushState = true) {
            const root = document.getElementById('kt_app_root');
            if (!root) { window.location.href = url; return; }
            
            if (isNavigating) return;
            if (url === currentUrl) return;

            isNavigating = true;
            showLoader();

            fetch(url, { credentials: 'same-origin' })
                .then(r => { if (!r.ok) throw new Error(r.status); return r.text(); })
                .then(html => {
                    if (pushState) history.pushState({ url }, '', url);
                    currentUrl = url;
                    
                    const t = html.match(/<title>(.*?)<\/title>/i);
                    if (t) document.title = t[1];
                    root.innerHTML = html;
                    
                    // CRITICAL: Re-initialize after SPA navigation
                    // window.LiveBladeRefresh();
                    if (typeof window.LiveBladeRefresh === 'function') {
                        window.LiveBladeRefresh();
                    }
                })
                .catch(err => {
                    console.error('[LiveBlade] navigate:', err);
                    root.innerHTML = '<p class="p-4 text-danger">Page not found.</p>';
                })
                .finally(() => {
                    hideLoader();
                    setTimeout(() => { isNavigating = false; }, 200);
                });
        }

        // Also expose navigateToGuestPage for backward compatibility
        window.navigateToGuestPage = (url) => navigate(url);
        window.reloadTo = (url) => { window.location.href = url; };

        window.addEventListener('popstate', e =>
            navigate(e.state?.url || window.location.pathname, false)
        );

        // Boot
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => {
                // Check if we need to navigate or just refresh components
                const root = document.getElementById('kt_app_root');
                if (root && (!root.children.length || root.innerHTML.trim() === '')) {
                    navigate(window.location.pathname, false);
                } else {
                    window.LiveBladeRefresh();
                }
            });
        } else {
            const root = document.getElementById('kt_app_root');
            if (root && (!root.children.length || root.innerHTML.trim() === '')) {
                navigate(window.location.pathname, false);
            } else {
                window.LiveBladeRefresh();
            }
        }

    })();
</script>



