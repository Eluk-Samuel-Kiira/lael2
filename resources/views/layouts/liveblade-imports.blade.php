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



