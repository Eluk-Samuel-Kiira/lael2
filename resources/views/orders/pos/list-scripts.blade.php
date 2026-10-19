{{-- ═══════════════════════════════════════════════════════════════
     POS — vanilla JS orchestrator.
     Renders the product pills and variant grid from /pos/search.
     No page reloads, no history, no location.href.
     ═══════════════════════════════════════════════════════════════ --}}
<script>
window.PosList = (function () {
    'use strict';

    const URL_SEARCH = '{{ route("pos.search") }}';
    const IS_SINGLE_SHOP = {{ $isSingleShop ? 'true' : 'false' }};
    const INITIAL_DEPARTMENT = @json($selectedDepartmentId);

    const state = {
        search:        '',
        departmentId:  INITIAL_DEPARTMENT || '',
        page:          1,
        isFetching:    false,
    };

    let searchDebounce = null;

    // Per-variant original stock, used for live badge updates
    const stockByVariant = new Map();
    const cardsByVariant = new Map();

    // ── Boot ────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', () => {
        // Clone the department select — drops any stale listeners
        // from old scripts that would otherwise reload the page.
        const oldSel = document.getElementById('departmentFilter');
        if (oldSel && oldSel.parentNode) {
            const fresh = oldSel.cloneNode(true);
            oldSel.parentNode.replaceChild(fresh, oldSel);
        }

        bindSearch();
        bindDepartment();

        if (IS_SINGLE_SHOP || state.departmentId) {
            fetchAndRender();
        } else {
            hideSkeleton();
        }
    });

    // ── Search wiring ───────────────────────────────────────────
    function bindSearch() {
        const input = document.getElementById('variantSearchInput');
        if (!input) return;

        input.addEventListener('input', function () {
            clearTimeout(searchDebounce);
            const term = this.value.trim();

            searchDebounce = setTimeout(() => {
                state.search = term;
                state.page   = 1;
                fetchAndRender();
            }, 400);
        });
    }

    // ── Department filter wiring — fetch, don't navigate ────────
    function bindDepartment() {
        const sel = document.getElementById('departmentFilter');
        if (!sel) return;

        sel.addEventListener('change', function () {
            const dept = this.value || '';
            if (!dept) return;

            state.departmentId = dept;
            state.search       = '';
            state.page         = 1;

            const input = document.getElementById('variantSearchInput');
            if (input) input.value = '';

            state.isFetching = false;
            fetchAndRender();
        });
    }

    // ── Fetch + render ──────────────────────────────────────────
    async function fetchAndRender() {
        if (state.isFetching) return;
        if (!IS_SINGLE_SHOP && !state.departmentId) {
            hideSkeleton();
            return;
        }

        state.isFetching = true;
        showSkeleton();

        const params = new URLSearchParams();
        if (state.search)       params.set('search', state.search);
        if (state.departmentId) params.set('department', state.departmentId);

        try {
            const res = await fetch(`${URL_SEARCH}?${params.toString()}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
                credentials: 'same-origin',
            });
            if (!res.ok) throw new Error('HTTP ' + res.status);
            const data = await res.json();

            if (!data.success) throw new Error(data.message || 'Fetch failed');

            if (!data.products || data.products.length === 0) {
                showNoResults(state.search);
            } else {
                renderSearchResults(data.products, data.is_single_shop);
                registerVariantCards();
            }
        } catch (err) {
            console.error('[PosList] fetch failed:', err);
            window.toastr?.error?.('{{ __("pagination.search_error") ?? "Error loading products." }}');
        } finally {
            state.isFetching = false;
            hideSkeleton();
        }
    }

    // ── Skeleton ────────────────────────────────────────────────
    function showSkeleton() {
        document.getElementById('variantTabSkeleton')?.classList.remove('d-none');
        document.getElementById('variantTabContent')?.classList.add('d-none');
    }

    function hideSkeleton() {
        document.getElementById('variantTabSkeleton')?.classList.add('d-none');
        document.getElementById('variantTabContent')?.classList.remove('d-none');
    }

    // ── Render pills + variant cards ────────────────────────────
    function renderSearchResults(products, isSingleShop) {
        const tabContent = document.getElementById('variantTabContent');
        if (!tabContent) return;
        tabContent.innerHTML = '';

        const productList = document.getElementById('productList');
        if (productList) {
            productList.querySelectorAll('.product-item').forEach(el => el.remove());
            const existingMsg = productList.querySelector('.search-result-msg');
            if (existingMsg) existingMsg.remove();
        }

        let firstProduct = true;
        const foundProductIds = new Set();

        products.forEach(product => {
            if (!product.variants || product.variants.length === 0) return;
            foundProductIds.add(product.id);

            if (productList) {
                const li = document.createElement('li');
                li.className = 'nav-item mb-3 me-0 product-item';
                li.style.minWidth = '180px';
                li.setAttribute('data-department', (product.departments || []).map(d => d.id).join(','));

                const imageUrl = productImageUrl(product.image_url);

                li.innerHTML = `
                    <a class="nav-link nav-link-border-solid btn btn-outline btn-active-color-primary product-card page-bg ${firstProduct ? 'active' : ''}"
                       data-bs-toggle="pill" href="#kt_pos_${product.id}">
                        <div class="product-icon">
                            <img src="${imageUrl}" class="w-50px" alt="" />
                        </div>
                        <div class="product-name-wrap">
                            <span class="product-name" data-bs-toggle="tooltip" title="${escapeHtml(product.name)}">
                                ${escapeHtml(product.name)}
                            </span>
                        </div>
                        <span class="product-options">
                            ${product.variants.length} {{ __('pagination._options') }}
                        </span>
                    </a>
                `;
                productList.appendChild(li);
            }

            const tabPane = document.createElement('div');
            tabPane.className = `tab-pane fade ${firstProduct ? 'show active' : ''}`;
            tabPane.id = `kt_pos_${product.id}`;

            const grid = document.createElement('div');
            grid.className = 'd-flex flex-wrap d-grid gap-5 gap-xxl-9 variant-container';

            product.variants.forEach(variant => {
                const strategy = product.inventory_strategy || 'quantity';
                const isRecipe = strategy === 'recipe' && !!product.recipe;
                const card = createVariantCardWithStrategy(
                    variant,
                    product,
                    isSingleShop,
                    strategy,
                    isRecipe,
                    variant.available_batches || [],
                    variant.available_serials || [],
                    variant.quantity_available || 0
                );
                grid.appendChild(card);
            });

            tabPane.appendChild(grid);
            tabContent.appendChild(tabPane);
            firstProduct = false;
        });

        const firstPill = document.querySelector('.product-item a[data-bs-toggle="pill"]');
        if (firstPill && window.bootstrap?.Tab) {
            bootstrap.Tab.getOrCreateInstance(firstPill).show();
        }

        if (typeof fitAllVariantNames === 'function') {
            fitAllVariantNames(tabContent);
        }
    }

    function showNoResults(searchTerm) {
        const tabContent = document.getElementById('variantTabContent');
        if (!tabContent) return;

        tabContent.innerHTML = `
            <div class="text-center py-10 w-100 no-results-message">
                <i class="ki-duotone ki-search-list fs-3x text-gray-400 mb-3 d-block">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                <span class="text-gray-500 fw-semibold fs-5">
                    ${searchTerm ? 'No products found for "' + escapeHtml(searchTerm) + '"' : '{{ __("pagination.no_products") ?? "No products available" }}'}
                </span>
            </div>
        `;

        const productList = document.getElementById('productList');
        if (productList) {
            productList.querySelectorAll('.product-item').forEach(el => el.remove());
            const existingMsg = productList.querySelector('.search-result-msg');
            if (existingMsg) existingMsg.remove();

            const msg = document.createElement('li');
            msg.className = 'search-result-msg nav-item me-3';
            msg.innerHTML = `<span class="fw-bold text-danger fs-6">${searchTerm ? 'No results' : '{{ __("pagination.not_assigned") }}'}</span>`;
            productList.prepend(msg);
        }
    }

    // ── Card factory ────────────────────────────────────────────
    function createVariantCardWithStrategy(variant, product, isSingleShop, strategy, isRecipe, batches, serials, quantityAvailable) {
        const card = document.createElement('div');
        card.className = 'card card-flush flex-row-fluid p-6 pb-5 mw-100 variant-item position-relative';

        const imageUrl = productVariantImageUrl(variant.image_url || product.image_url);

        card.setAttribute('data-name', (variant.name || product.name).toLowerCase());
        card.setAttribute('data-product', product.id);
        card.setAttribute('data-variant-id', variant.id);
        card.setAttribute('data-price', variant.effective_price ?? variant.selling_price);
        card.setAttribute('data-effective-price', variant.effective_price ?? variant.selling_price);
        card.setAttribute('data-effective-selling-price', variant.effective_selling_price ?? variant.selling_price);
        card.setAttribute('data-effective-cost-price', variant.effective_cost_price ?? 0);
        card.setAttribute('data-effective-discount-price', variant.effective_discount_price ?? 0);
        card.setAttribute('data-pricing-source', variant.pricing_source ?? 'variant');
        card.setAttribute('data-has-custom-pricing', (variant.has_custom_pricing ?? false) ? 'true' : 'false');
        card.setAttribute('data-image', imageUrl);
        card.setAttribute('data-taxes', JSON.stringify(variant.applicable_taxes || []));
        card.setAttribute('data-promotions', JSON.stringify(variant.applicable_promotions || []));
        card.setAttribute('data-strategy', strategy);
        card.setAttribute('data-is-recipe', isRecipe ? 'true' : 'false');
        card.setAttribute('data-batches', JSON.stringify(batches));
        card.setAttribute('data-serials', JSON.stringify(serials));
        card.setAttribute('data-quantity-available', quantityAvailable);
        card.setAttribute('data-variant-name', variant.name || product.name);

        if (!isSingleShop && variant.inventory_by_dept) {
            card.setAttribute('data-inventory', JSON.stringify(variant.inventory_by_dept || {}));
        }

        card.onclick = function () { handleVariantClick(this); };
        card.style.cursor = 'pointer';

        const strategyColors = { quantity:'primary', batch:'info', serial:'warning', recipe:'success' };
        const strategyLabels = {
            quantity: '{{ __("passwords.quantity") }}',
            batch:    '{{ __("passwords.batch") }}',
            serial:   '{{ __("passwords.serial") }}',
            recipe:   '{{ __("passwords.recipe") }}',
        };
        const strategyColor = strategyColors[strategy] || 'primary';
        const strategyLabel = strategyLabels[strategy] || strategy;
        const badgeClass = 'bg-' + strategyColor;

        const hasTaxes   = variant.applicable_taxes && variant.applicable_taxes.length > 0;
        const hasPromos  = variant.applicable_promotions && variant.applicable_promotions.length > 0;
        const hasBatches = strategy === 'batch' && batches && batches.length > 0;
        const hasSerials = strategy === 'serial' && serials && serials.length > 0;
        const fullName   = variant.name || product.name;

        card.innerHTML = `
            <span class="badge ${badgeClass} text-white position-absolute top-0 start-50 translate-middle-x mt-2 px-3 py-2 shadow-sm"
                style="z-index: 10; min-width: 70px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; letter-spacing: 0.3px;">
                ${strategyLabel}
                ${isRecipe ? `<span class="badge bg-warning text-dark ms-1" style="font-size: 0.55rem; padding: 1px 6px; border-radius: 10px;"><i class="bi bi-info-circle me-1"></i>Recipe</span>` : ''}
            </span>

            ${hasTaxes ? `<span class="badge bg-danger text-white position-absolute top-0 start-0 m-2 px-3 py-2 shadow-sm" style="z-index: 10;">{{ __("passwords._tax") }}</span>` : ''}
            ${hasPromos ? `<span class="badge bg-success text-white position-absolute top-0 end-0 m-2 px-3 py-2 shadow-sm" style="z-index: 10;">{{ __("passwords._promo") }}</span>` : ''}
            ${hasBatches ? `<span class="badge bg-info text-white position-absolute bottom-0 start-50 translate-middle-x mb-2 px-2 py-1 shadow-sm" style="z-index: 10; font-size: 0.55rem; border-radius: 10px;">${batches.length} {{ __("passwords.batches_available") }}</span>` : ''}
            ${hasSerials ? `<span class="badge bg-warning text-dark position-absolute bottom-0 start-50 translate-middle-x mb-2 px-2 py-1 shadow-sm" style="z-index: 10; font-size: 0.55rem; border-radius: 10px;">${serials.length} {{ __("passwords.serials_available") }}</span>` : ''}

            <div class="card-body text-center">
                <img src="${imageUrl}"
                     class="rounded-3 mb-4 w-150px h-150px w-xxl-200px h-xxl-200px"
                     alt="${escapeHtml(fullName)}"
                     onerror="this.src='{{ asset('assets/media/stock/ecommerce/2.png') }}'" />

                <div class="mb-2">
                    <div class="text-center">
                        <div class="variant-name-wrap">
                            <span class="variant-name" data-bs-toggle="tooltip" title="${escapeHtml(fullName)}">
                                ${escapeHtml(fullName)}
                            </span>
                        </div>
                        <span class="text-gray-500 fw-semibold d-block fs-6 mt-n1 variant-qty">
                            ${quantityAvailable} {{ __('pagination._available') }}
                        </span>
                        ${hasBatches ? `<span class="text-muted d-block fs-8">${batches.length} {{ __("passwords.batches") }}</span>` : ''}
                        ${hasSerials ? `<span class="text-muted d-block fs-8">${serials.length} {{ __("passwords.serials") }}</span>` : ''}
                    </div>
                </div>

                <span class="text-success text-end fw-bold fs-1">
                    ${variant.effective_price ?? variant.selling_price} {{ currency_symbol() }}
                    ${variant.has_custom_pricing ? `<span class="badge badge-light-warning fs-8 ms-1" title="${variant.pricing_source === 'item' ? 'Item-level price' : variant.pricing_source === 'batch' ? 'Batch-level price' : variant.pricing_source === 'serial' ? 'Serial-level price' : 'Custom price'}"><i class="bi bi-pencil-square"></i></span>` : ''}
                </span>
            </div>
        `;

        return card;
    }

    function productImageUrl(path) {
        if (!path) return '{{ asset("assets/media/stock/ecommerce/2.png") }}';
        const clean = String(path).replace(/^\/+/, '');
        return '{{ asset("storage") }}/' + clean;
    }
    function productVariantImageUrl(path) {
        return productImageUrl(path);
    }

    function escapeHtml(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    // ── Live stock badges ───────────────────────────────────────
    function cartQtyForVariant(variantId) {
        if (typeof cart === 'undefined' || !Array.isArray(cart)) return 0;
        const idStr = String(variantId);
        let q = 0;
        cart.forEach(item => {
            if (String(item.id) === idStr) q += Number(item.quantity) || 0;
        });
        return q;
    }

    function registerVariantCards() {
        document.querySelectorAll('.variant-item[data-variant-id]').forEach(card => {
            if (card.dataset.stockRegistered) return;
            const vid = card.getAttribute('data-variant-id');
            if (!vid) return;

            const total = parseInt(card.getAttribute('data-quantity-available'), 10) || 0;
            stockByVariant.set(vid, total);

            if (!cardsByVariant.has(vid)) cardsByVariant.set(vid, new Set());
            cardsByVariant.get(vid).add(card);

            card.dataset.stockRegistered = '1';
        });
        syncVariantStock();
    }

    function syncVariantStock() {
        cardsByVariant.forEach((cards, variantId) => {
            const total = stockByVariant.get(variantId);
            if (total === undefined) return;

            const inCart = cartQtyForVariant(variantId);
            const remaining = Math.max(0, total - inCart);

            cards.forEach(card => {
                if (!card.isConnected) { cards.delete(card); return; }

                card.setAttribute('data-quantity-available', String(remaining));

                const badge = card.querySelector('.variant-qty');
                if (badge) {
                    const suffix = badge.textContent.replace(/^[0-9.\s]+/, '').trim();
                    badge.textContent = `${remaining} ${suffix || ''}`.trim();
                }

                const inv = card.getAttribute('data-inventory');
                if (inv) {
                    try {
                        const parsed = JSON.parse(inv);
                        const deptFilter = document.getElementById('departmentFilter');
                        const deptId = deptFilter ? String(deptFilter.value) : '';
                        if (deptId && parsed[deptId]) {
                            parsed[deptId].quantity = remaining;
                            card.setAttribute('data-inventory', JSON.stringify(parsed));
                        }
                    } catch (_) {}
                }

                if (remaining <= 0 && card.getAttribute('data-is-recipe') !== 'true') {
                    card.classList.add('out-of-stock');
                    card.style.opacity = '0.5';
                } else {
                    card.classList.remove('out-of-stock');
                    card.style.opacity = '1';
                }
            });
        });
    }

    function wrap(name) {
        const orig = window[name];
        if (typeof orig !== 'function' || orig.__stockWrapped) return;
        const wrapped = function (...args) {
            const ret = orig.apply(this, args);
            try { syncVariantStock(); } catch (e) { console.error('[stock-sync]', e); }
            return ret;
        };
        wrapped.__stockWrapped = true;
        window[name] = wrapped;
    }

    document.addEventListener('DOMContentLoaded', () => {
        ['addToCart','removeFromCart','clearCart','increaseQuantity','decreaseQuantity','updateQuantity']
            .forEach(wrap);
    });

    document.addEventListener('shown.bs.tab', registerVariantCards);

    // ── Public API ──────────────────────────────────────────────
    window.PosList = {
        refresh: fetchAndRender,
        setDepartment(id) {
            state.departmentId = id || '';
            state.isFetching = false;
            fetchAndRender();
        },
    };
    return window.PosList;
})();
</script>