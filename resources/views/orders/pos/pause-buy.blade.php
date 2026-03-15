{{--
════════════════════════════════════════════════════════════════
  pause-buy.blade.php  —  "Pause & Resume" partial
  Zero custom CSS — 100% Metronic utility classes.

  @include('orders.pos.pause-buy')

  Route (BEFORE Route::resource):
    Route::get('/orders/paused', [POSController::class, 'getPausedOrders'])
         ->name('orders.paused');
════════════════════════════════════════════════════════════════
--}}

{{-- ── Trigger button — paste into your top-bar ──────────────────
<div class="position-relative" id="pause-buy-trigger">
    <button type="button"
            class="btn btn-light-warning fw-bold h-45px"
            data-bs-toggle="offcanvas"
            data-bs-target="#pauseBuyDrawer"
            aria-controls="pauseBuyDrawer">
        <i class="ki-duotone ki-time fs-2 me-2">
            <span class="path1"></span><span class="path2"></span>
        </i>
        {{ __('pagination.pause_buy') }}
    </button>
    <span id="pause-buy-badge"
          class="position-absolute top-0 start-100 translate-middle
                 badge badge-circle badge-danger fs-9 fw-bold"
          style="display:none;">
        0
    </span>
</div>
────────────────────────────────────────────────────────────── --}}


{{-- ── OFFCANVAS DRAWER ─────────────────────────────────────── --}}
<div class="offcanvas offcanvas-end" tabindex="-1"
     id="pauseBuyDrawer" aria-labelledby="pauseBuyDrawerLabel"
     style="width:420px;max-width:95vw;">

    {{-- Header --}}
    <div class="offcanvas-header bg-dark px-7 py-5">
        <div>
            <h5 class="offcanvas-title fw-bold text-white fs-4 mb-1" id="pauseBuyDrawerLabel">
                <i class="ki-duotone ki-time fs-2 text-warning me-2">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                {{ __('pagination.pause_buy') }}
            </h5>
            <div class="text-white opacity-50 fs-7">{{ __('pagination.pause_buy_subtitle') }}</div>
        </div>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
    </div>

    {{-- Body --}}
    <div class="offcanvas-body p-0 bg-light">

        {{-- Loading --}}
        <div id="pb-loading" class="d-flex flex-column align-items-center justify-content-center py-20">
            <span class="spinner-border spinner-border text-primary mb-4"></span>
            <span class="text-muted fs-6 fw-semibold">{{ __('pagination.loading_paused_orders') }}</span>
        </div>

        {{-- Empty --}}
        <div id="pb-empty" class="d-flex flex-column align-items-center justify-content-center py-20" style="display:none!important;">
            <i class="ki-duotone ki-cart fs-5x text-gray-300 mb-4">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span class="fw-bold text-gray-700 fs-5 mb-1">{{ __('pagination.no_paused_orders') }}</span>
            <span class="text-muted fs-7">{{ __('pagination.no_paused_orders_hint') }}</span>
        </div>

        {{-- Error --}}
        <div id="pb-error" class="d-flex flex-column align-items-center justify-content-center py-20" style="display:none!important;">
            <i class="ki-duotone ki-warning-2 fs-5x text-danger mb-4">
                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
            </i>
            <span class="fw-bold text-danger fs-6 mb-3" id="pb-error-msg">
                {{ __('pagination.error_loading_orders') }}
            </span>
            <button type="button" class="btn btn-sm btn-light-primary mb-4" onclick="pbLoad()">
                <i class="ki-duotone ki-arrows-circle fs-4 me-1">
                    <span class="path1"></span><span class="path2"></span>
                </i>
                {{ __('pagination.retry') }}
            </button>
            {{-- Debug box: raw server response when not JSON --}}
            <div id="pb-debug-info"
                 class="alert alert-warning w-100 mx-4 fs-8 font-monospace text-break"
                 style="display:none;max-height:120px;overflow:auto;">
            </div>
        </div>

        {{-- Orders list --}}
        <div id="pb-list" class="py-2" style="display:none!important;"></div>

    </div>
</div>


{{-- ── CONFIRM REPLACE MODAL ───────────────────────────────── --}}
<div class="modal fade" id="pbConfirmModal" tabindex="-1" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-sm">
        <div class="modal-content shadow-lg">

            <div class="modal-header border-0 pb-0 bg-light-warning justify-content-center pt-8">
                <i class="ki-duotone ki-information-5 fs-5x text-warning">
                    <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                </i>
            </div>

            <div class="modal-body text-center pt-4 pb-2">
                <h5 class="fw-bold text-gray-800 mb-2">{{ __('pagination.replace_cart_title') }}</h5>
                <p class="text-muted fs-6 mb-0">{{ __('pagination.replace_cart_message') }}</p>
            </div>

            <div class="modal-footer border-0 justify-content-center gap-3 pb-8">
                <button type="button" class="btn btn-light fw-bold min-w-100px" data-bs-dismiss="modal">
                    {{ __('pagination.cancel') }}
                </button>
                <button type="button" class="btn btn-warning fw-bold min-w-100px" id="pb-confirm-restore">
                    <i class="ki-duotone ki-arrows-circle fs-3 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ __('pagination.yes_restore') }}
                </button>
            </div>

        </div>
    </div>
</div>


{{-- ── SUCCESS TOAST (Metronic alert pill, fixed bottom-center) ── --}}
<div id="pb-restore-toast"
     class="position-fixed bottom-0 start-50 translate-middle-x mb-8
            alert alert-success d-flex align-items-center gap-3
            shadow-lg rounded-pill px-8 py-4 fw-semibold fs-6 z-index-3"
     style="opacity:0;pointer-events:none;transition:opacity .25s;white-space:nowrap;">
    <i class="ki-duotone ki-check-circle fs-2 text-success">
        <span class="path1"></span><span class="path2"></span>
    </i>
    <span id="pb-restore-toast-msg">{{ __('pagination.cart_restored') }}</span>
</div>


{{-- ── JAVASCRIPT  (logic unchanged — only pbRenderCard HTML updated) ── --}}
<script>
(function () {

    var pbPendingOrder = null;
    var pbDrawerEl     = document.getElementById('pauseBuyDrawer');
    var pbConfirmModal = null;
    var PB_ENDPOINT    = '{{ route("orders.paused") }}';

    // ── tiny helpers ──────────────────────────────────────────
    function show(id) {
        var el = document.getElementById(id);
        if (el) { el.style.display = ''; el.style.removeProperty('display'); el.classList.remove('d-none'); }
    }
    function hide(id) {
        var el = document.getElementById(id);
        if (el) { el.style.setProperty('display', 'none', 'important'); }
    }
    function g(id) { return document.getElementById(id); }

    // ── Load ──────────────────────────────────────────────────
    window.pbLoad = function () {
        show('pb-loading');
        hide('pb-empty');
        hide('pb-error');
        hide('pb-list');
        var dbg = g('pb-debug-info');
        if (dbg) dbg.style.display = 'none';
        g('pb-list').innerHTML = '';

        fetch(PB_ENDPOINT, {
            method: 'GET',
            headers: {
                'Accept':       'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
            },
            credentials: 'same-origin',
        })
        .then(function (response) {
            var status      = response.status;
            var contentType = response.headers.get('content-type') || '';

            if (!contentType.includes('application/json')) {
                return response.text().then(function (body) {
                    hide('pb-loading');
                    var errMsg = g('pb-error-msg');
                    if (errMsg) errMsg.textContent = 'HTTP ' + status + ' — server did not return JSON';
                    var dbg = g('pb-debug-info');
                    if (dbg) { dbg.style.display = 'block'; dbg.textContent = body.substring(0, 500) || '(empty)'; }
                    show('pb-error');
                    console.error('[PauseBuy] non-JSON response (HTTP ' + status + '):', body.substring(0, 500));
                });
            }

            return response.json().then(function (data) {
                hide('pb-loading');

                if (status === 401 || status === 403) {
                    var errMsg = g('pb-error-msg');
                    if (errMsg) errMsg.textContent = data.message || '{{ __("payments.not_authorized") }}';
                    show('pb-error');
                    return;
                }

                if (!response.ok) {
                    var errMsg = g('pb-error-msg');
                    if (errMsg) errMsg.textContent = data.message || '{{ __("pagination.error_loading_orders") }}';
                    if (data.debug) console.error('[PauseBuy] server error:', data.debug);
                    show('pb-error');
                    return;
                }

                var orders = data.orders || [];

                var badge = g('pause-buy-badge');
                if (badge) {
                    badge.style.display = orders.length > 0 ? 'flex' : 'none';
                    badge.textContent   = orders.length > 9 ? '9+' : String(orders.length);
                }

                if (!orders.length) { show('pb-empty'); return; }

                var list = g('pb-list');
                list.innerHTML = orders.map(pbRenderCard).join('');
                show('pb-list');
            });
        })
        .catch(function (err) {
            hide('pb-loading');
            var errMsg = g('pb-error-msg');
            if (errMsg) errMsg.textContent = '{{ __("pagination.error_loading_orders") }}';
            show('pb-error');
            console.error('[PauseBuy] fetch error:', err);
        });
    };

    // ── Render card — 100% Metronic classes ──────────────────
    function pbRenderCard(order) {
        var SYM       = window.POS_CURRENCY_SYM || '';
        var items     = order.items || [];
        var itemCount = items.reduce(function (s, i) { return s + (parseInt(i.quantity) || 1); }, 0);

        // Item chips — up to 4 then "+N more"
        var chips = items.slice(0, 4).map(function (item) {
            return '<span class="badge badge-light-primary fw-semibold fs-8 me-1 mb-1">' +
                '<span class="badge badge-primary fs-9 me-1">' + (item.quantity || 1) + '</span>' +
                escHtml(item.name || item.item_name || 'Item') +
                '</span>';
        }).join('');

        if (items.length > 4) {
            chips += '<span class="badge badge-light-success fw-semibold fs-8 me-1 mb-1">+' +
                (items.length - 4) + ' {{ __("pagination.more") }}</span>';
        }

        var total    = parseFloat(order.total || 0).toFixed(2);
        var customer = escHtml(order.customer_name || '{{ __("pagination.guest") }}');
        var orderNo  = escHtml(order.order_number  || '#' + order.id);
        var timeAgo  = pbTimeAgo(order.created_at);

        return '<div class="card card-flush mx-4 my-3 cursor-pointer border border-2 border-transparent' +
                    ' hover-border-primary transition-all"' +
                ' onclick="pbClickCard(this)"' +
                ' data-order=\'' + escAttr(JSON.stringify(order)) + '\'>' +
            '<div class="card-body p-5">' +

                // Top row: order info + total
                '<div class="d-flex align-items-start justify-content-between gap-3 mb-3">' +

                    '<div class="d-flex flex-column gap-1">' +
                        // Order number badge
                        '<span class="badge badge-light-primary fw-bold fs-9 text-uppercase w-auto align-self-start">' +
                            orderNo +
                        '</span>' +

                        // Customer name
                        '<div class="d-flex align-items-center gap-2 mt-1">' +
                            '<span class="symbol symbol-30px symbol-circle">' +
                                '<span class="symbol-label bg-light-primary">' +
                                    '<i class="ki-duotone ki-user fs-5 text-primary">' +
                                    '<span class="path1"></span><span class="path2"></span></i>' +
                                '</span>' +
                            '</span>' +
                            '<span class="fw-bold text-gray-800 fs-6">' + customer + '</span>' +
                        '</div>' +

                        // Time + item count
                        '<div class="d-flex align-items-center gap-2 text-muted fs-7">' +
                            '<i class="ki-duotone ki-time fs-7">' +
                            '<span class="path1"></span><span class="path2"></span></i>' +
                            timeAgo +
                            '<span class="bullet bullet-dot"></span>' +
                            itemCount + ' {{ __("pagination.items_label") }}' +
                        '</div>' +
                    '</div>' +

                    // Total amount
                    '<div class="text-primary fw-bolder fs-3 text-nowrap">' +
                        SYM + total +
                    '</div>' +

                '</div>' +

                // Item chips
                (chips ?
                    '<div class="separator separator-dashed mb-3"></div>' +
                    '<div class="d-flex flex-wrap gap-1">' + chips + '</div>'
                : '') +

            '</div>' +
        '</div>';
    }

    // ── Click card ────────────────────────────────────────────
    window.pbClickCard = function (el) {
        var raw = el.getAttribute('data-order');
        if (!raw) return;
        var order;
        try { order = JSON.parse(raw); } catch (e) { return; }
        pbPendingOrder = order;

        if (typeof cart !== 'undefined' && cart.length > 0) {
            if (!pbConfirmModal) pbConfirmModal = new bootstrap.Modal(g('pbConfirmModal'));
            pbConfirmModal.show();
        } else {
            pbDoRestore(order);
        }
    };

    // ── Confirm button ────────────────────────────────────────
    document.addEventListener('click', function (e) {
        if (e.target.closest('#pb-confirm-restore')) {
            if (pbConfirmModal) pbConfirmModal.hide();
            if (pbPendingOrder) pbDoRestore(pbPendingOrder);
        }
    });

    // ── Restore cart ──────────────────────────────────────────
    function pbDoRestore(order) {
        var drawer = bootstrap.Offcanvas.getInstance(pbDrawerEl);
        if (drawer) drawer.hide();

        if (typeof clearCart === 'function') clearCart();

        var items  = order.items || [];
        var failed = 0;

        items.forEach(function (item) {
            var variant = {
                id:                 item.variant_id || item.id,
                name:               item.name || item.item_name || 'Item',
                price:              parseFloat(item.unit_price || item.price || 0),
                image:              item.image_url || item.image || '{{ asset("assets/media/avatars/blank.png") }}',
                quantity_available: (item.quantity_available !== undefined) ? item.quantity_available : 9999,
                taxes:              Array.isArray(item.taxes)      ? item.taxes      : [],
                promotions:         Array.isArray(item.promotions) ? item.promotions : [],
            };

            var targetQty = parseInt(item.quantity) || 1;
            for (var q = 0; q < targetQty; q++) {
                if (typeof addToCart === 'function') {
                    addToCart(variant);
                } else {
                    failed++;
                    break;
                }
            }
        });

        pbRestoreCustomer(order);

        // ── KEY: stamp the existing order onto the global cart state ──
        // processPayment() checks window.resumedOrderId — if set it
        // skips creating a new order and uses this one instead.
        window.resumedOrderId     = order.id;
        window.resumedOrderNumber = order.order_number;

        var custLabel = order.customer_name || '{{ __("pagination.guest") }}';
        pbShowToast('✓ ' + custLabel + ' — {{ __("pagination.cart_restored") }}');

        if (failed > 0) console.warn('[PauseBuy] ' + failed + ' items could not be restored.');
    }

    // ── Restore customer panel ────────────────────────────────
    function pbRestoreCustomer(order) {
        var btnExisting = g('btn-pick-existing');
        var btnNew      = g('btn-pick-new');

        if (order.customer_id) {
            var sel = g('cust-existing-select');
            if (sel) {
                for (var i = 0; i < sel.options.length; i++) {
                    if (String(sel.options[i].value) === String(order.customer_id)) {
                        sel.selectedIndex = i;
                        break;
                    }
                }
            }
            if (btnExisting) btnExisting.click();

        } else if (order.customer_name && order.customer_name !== '{{ __("pagination.guest") }}') {
            var inp = g('cust-new-input');
            if (inp) inp.value = order.customer_name;
            if (btnNew) btnNew.click();
        }
    }

    // ── Toast ─────────────────────────────────────────────────
    function pbShowToast(msg) {
        var toast    = g('pb-restore-toast');
        var toastMsg = g('pb-restore-toast-msg');
        if (!toast) return;
        if (toastMsg) toastMsg.textContent = msg;
        toast.style.opacity      = '1';
        toast.style.pointerEvents = 'auto';
        setTimeout(function () {
            toast.style.opacity       = '0';
            toast.style.pointerEvents = 'none';
        }, 3000);
    }

    // ── Helpers ───────────────────────────────────────────────
    function escHtml(str) {
        return String(str)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;')
            .replace(/>/g, '&gt;').replace(/"/g, '&quot;');
    }
    function escAttr(str) {
        return String(str).replace(/'/g, '&#39;').replace(/\\/g, '\\\\');
    }
    function pbTimeAgo(dateStr) {
        if (!dateStr) return '—';
        var diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
        if (diff < 60)    return '{{ __("pagination.just_now") }}';
        if (diff < 3600)  return Math.floor(diff / 60)   + ' {{ __("pagination.mins_ago") }}';
        if (diff < 86400) return Math.floor(diff / 3600) + ' {{ __("pagination.hrs_ago") }}';
        return Math.floor(diff / 86400) + ' {{ __("pagination.days_ago") }}';
    }

    // ── Auto-load when drawer opens ───────────────────────────
    if (pbDrawerEl) {
        pbDrawerEl.addEventListener('show.bs.offcanvas', function () { pbLoad(); });
    }

    // ── Silent badge update on page load ─────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        fetch(PB_ENDPOINT, {
            headers: {
                'Accept':       'application/json',
                'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') || {}).content || '',
            },
            credentials: 'same-origin',
        })
        .then(function (r) {
            var ct = r.headers.get('content-type') || '';
            return ct.includes('application/json') ? r.json() : { orders: [] };
        })
        .then(function (data) {
            var count = (data.orders || []).length;
            var badge = g('pause-buy-badge');
            if (badge && count > 0) {
                badge.style.display = 'flex';
                badge.textContent   = count > 9 ? '9+' : String(count);
            }
        })
        .catch(function () {});
    });

})();
</script>