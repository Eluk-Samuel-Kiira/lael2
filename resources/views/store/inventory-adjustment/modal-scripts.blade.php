<script>
(function () {
    'use strict';

    const URL_DETAILS  = '{{ route("stocks.details",  ["id" => "__ID__"]) }}';
    const URL_ADJUST   = '{{ route("stocks.update",   ["stock" => "__ID__"]) }}';
    const URL_TRANSFER = '{{ route("stocks.transfer", ["id" => "__ID__"]) }}';
    const CSRF = document.querySelector('meta[name="csrf-token"]')?.content || '';

    /* ════════════════════════════════════════════════════════════
       Shared helpers
       ════════════════════════════════════════════════════════════ */
    function esc(t) {
        if (t === null || t === undefined) return '';
        const d = document.createElement('div');
        d.textContent = String(t);
        return d.innerHTML;
    }

    function productImageUrl(path) {
        if (!path) return '{{ asset("assets/media/stock/ecommerce/2.png") }}';
        const clean = String(path).replace(/^\/+/, '');
        return '{{ asset("storage") }}/' + clean;
    }

    async function fetchDetails(id) {
        const res = await fetch(URL_DETAILS.replace('__ID__', id), {
            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok || json.success === false) {
            throw new Error(json.message || ('HTTP ' + res.status));
        }
        return json;
    }

    function setLoading(btn, busy) {
        if (!btn) return;
        if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';

        const label    = btn.querySelector('.indicator-label');
        const progress = btn.querySelector('.indicator-progress');

        if (progress && !progress.dataset.overlayInit) {
            Object.assign(progress.style, {
                position: 'absolute', top: '50%', left: '50%',
                transform: 'translate(-50%, -50%)',
                alignItems: 'center', gap: '0.5rem',
                whiteSpace: 'nowrap', pointerEvents: 'none',
            });
            progress.dataset.overlayInit = '1';
        }

        if (busy) {
            btn.style.minWidth = (btn.offsetWidth + 50) + 'px'; 
            if (label)    label.style.visibility = 'hidden';
            if (progress) {
                progress.classList.remove('d-none');
                progress.style.display = 'inline-flex';
            }
            btn.disabled = true;
        } else {
            btn.disabled = false;
            btn.style.minWidth = '';
            if (label)    label.style.visibility = '';
            if (progress) {
                progress.classList.add('d-none');
                progress.style.display = 'none';
            }
        }
    }

    /* ── Panel shell: replaces drawer body content ──────────── */
    function panelShell(title, bodyHtml, footerHtml) {
        const body = document.getElementById('stockDrawerContent');
        if (!body) return null;

        body.classList.remove('d-none');
        body.innerHTML = `
            <div class="d-flex align-items-center justify-content-between mb-4">
                <h5 class="fw-bold text-gray-800 mb-0">${title}</h5>
                <button type="button" class="btn btn-sm btn-light" data-panel-back>
                    <i class="bi bi-arrow-left me-1"></i>{{ __('auth._back') ?? 'Back' }}
                </button>
            </div>
            <div data-panel-body>${bodyHtml}</div>
            <div class="d-flex justify-content-end gap-2 mt-5" data-panel-footer>${footerHtml}</div>
        `;

        // Hide the drawer's standard action bar while a panel is showing
        document.getElementById('stockDrawerFooter')?.classList.add('d-none');

        body.querySelectorAll('[data-panel-back]').forEach(btn => {
            btn.addEventListener('click', () => {
                // Restore the read-only view from cache
                const id = currentPanelId();
                if (id && window.StockDrawer?.reload) window.StockDrawer.reload(id);
            });
        });

        return body;
    }

    let activePanelId = null;
    function currentPanelId() { return activePanelId; }

    /* ════════════════════════════════════════════════════════════
       StockAdjustPanel — inline in the drawer
       ════════════════════════════════════════════════════════════ */
    window.StockAdjustPanel = (function () {

        async function open(id) {
            activePanelId = id;

            const body = document.getElementById('stockDrawerContent');
            if (!body) return;
            body.classList.remove('d-none');
            body.innerHTML = `<div class="text-center py-8"><span class="spinner-border text-primary"></span></div>`;
            document.getElementById('stockDrawerFooter')?.classList.add('d-none');

            try {
                const data = await fetchDetails(id);
                const item = data.item;
                render(item);
            } catch (err) {
                console.error('[adjust-panel] load failed:', err);
                body.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>${esc(err.message)}
                </div>`;
            }
        }

        function render(item) {
            const overall = Number(item.overall_quantity || 0);
            const current = Number(item.quantity_allocated || 0);

            const bodyHtml = `
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="${productImageUrl(item.variant_image)}"
                         class="rounded-3 border" style="width:56px;height:56px;object-fit:cover;"
                         onerror="this.src='{{ asset('assets/media/stock/ecommerce/2.png') }}'">
                    <div>
                        <div class="fw-bold text-gray-800">${esc(item.variant_name || '—')}</div>
                        <div class="text-muted fs-7">
                            SKU <code>${esc(item.variant_sku || '—')}</code>
                            · ${esc(item.location_name || '—')} / ${esc(item.department_name || '—')}
                        </div>
                    </div>
                </div>

                <div id="adjustError" class="alert alert-danger d-none" role="alert"></div>

                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('pagination.overall_quantity') }}</label>
                    <input type="text" class="form-control text-center fs-3 fw-bold text-success"
                           id="adjustOverallQty" value="${overall}" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('passwords.current_stock_level') }}</label>
                    <input type="text" class="form-control text-center fs-3 fw-bold text-warning"
                           id="adjustCurrentStock" value="${current}" readonly>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('passwords.adjustment_amount') }}</label>
                    <input type="number" class="form-control text-center fs-3 fw-bold text-gray-800"
                           id="adjustAmount" name="adjust_amount"
                           value="0"
                           data-item-id="${item.id}"
                           data-overall="${overall}"
                           data-current="${current}"
                           min="-${current}" max="${overall}">
                    <small class="text-muted">
                        Positive = add from overall | Negative = return to overall
                        (Min: -${current} | Max: ${overall})
                    </small>
                    <div id="adjustAmountError" class="text-danger mt-1" style="display:none;"></div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">{{ __('passwords.new_stock_level') }}</label>
                    <input type="text" class="form-control text-center fs-3 fw-bold text-success"
                           id="adjustNewStock" value="${current}" readonly>
                </div>

                <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
                    <i class="bi bi-info-circle fs-4 me-2"></i>
                    <div>
                        <span class="fw-bold">{{ __('passwords.adjustment_summary') }}</span><br>
                        <span id="adjustmentSummary">{{ __('passwords.no_adjustment_yet') }}</span>
                    </div>
                </div>
            `;

            const footerHtml = `
                <button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('auth._discard') }}</button>
                <button type="button" class="btn btn-primary" data-adjust-submit data-item-id="${item.id}">
                    <span class="indicator-label">{{ __('auth._update') }}</span>
                    <span class="indicator-progress d-none">{{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            `;

            const body = panelShell('{{ __("passwords.adjust_stock_level") }}', bodyHtml, footerHtml);
            if (!body) return;

            bindPreview(body);
            bindSubmit(body, item.id);
        }

        function bindPreview(root) {
            const input = root.querySelector('#adjustAmount');
            if (!input) return;

            const recalc = () => {
                const overall = parseInt(input.dataset.overall, 10) || 0;
                const current = parseInt(input.dataset.current, 10) || 0;
                let v = parseInt(input.value, 10);
                if (isNaN(v)) v = 0;

                const newStock   = current + v;
                const newOverall = overall - v;

                root.querySelector('#adjustNewStock').value    = newStock;
                root.querySelector('#adjustOverallQty').value  = newOverall;

                let error = null;
                if (v > 0 && v > overall) error = `Cannot allocate more than available. Available: ${overall}`;
                if (v < 0 && Math.abs(v) > current) error = `Cannot remove more than allocated. Available: ${current}`;

                const errEl  = root.querySelector('#adjustAmountError');
                const bigErr = root.querySelector('#adjustError');
                const btn    = root.querySelector('[data-adjust-submit]');

                if (error) {
                    if (errEl)  { errEl.textContent = error; errEl.style.display = 'block'; }
                    if (bigErr) { bigErr.textContent = error; bigErr.classList.remove('d-none'); }
                    input.classList.add('is-invalid');
                    if (btn) btn.disabled = true;
                } else {
                    if (errEl)  { errEl.textContent = ''; errEl.style.display = 'none'; }
                    if (bigErr) { bigErr.textContent = ''; bigErr.classList.add('d-none'); }
                    input.classList.remove('is-invalid');
                    if (btn) btn.disabled = false;
                }

                const sumEl = root.querySelector('#adjustmentSummary');
                if (sumEl) {
                    if (v === 0) {
                        sumEl.textContent = '{{ __("passwords.no_adjustment_yet") }}';
                    } else {
                        const dir = v > 0 ? 'Added to department' : 'Returned to overall';
                        sumEl.innerHTML = `
                            <span class="text-${v > 0 ? 'success' : 'warning'} fw-bold">
                                ${dir}: ${Math.abs(v)} units
                            </span>
                            <span class="text-muted ms-2">
                                | Department: ${current} → ${newStock}
                                | Overall: ${overall} → ${newOverall}
                            </span>
                        `;
                    }
                }
            };

            input.addEventListener('input', recalc);
            input.addEventListener('change', recalc);
        }

        function bindSubmit(root, id) {
            root.querySelector('[data-adjust-submit]')?.addEventListener('click', async (ev) => {
                const btn      = ev.currentTarget;
                const overall  = parseInt(root.querySelector('#adjustOverallQty').value, 10) || 0;
                const current  = parseInt(root.querySelector('#adjustCurrentStock').value, 10) || 0;
                const amount   = parseInt(root.querySelector('#adjustAmount').value, 10) || 0;
                const errBox   = root.querySelector('#adjustError');

                errBox?.classList.add('d-none');

                // We use *original* values for the payload — read from the input's data attrs
                const origOverall = parseInt(root.querySelector('#adjustAmount').dataset.overall, 10) || 0;
                const origCurrent = parseInt(root.querySelector('#adjustAmount').dataset.current, 10) || 0;

                setLoading(btn, true);
                try {
                    const res = await fetch(URL_ADJUST.replace('__ID__', id), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN':     CSRF,
                            'Accept':           'application/json',
                            'Content-Type':     'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            _method:                 'PUT', 
                            overal_quantity_at_hand: origOverall,
                            current_quantity:        origCurrent,
                            adjust_amount:           amount,
                        }),
                    });
                    const json = await res.json().catch(() => ({}));

                    if (res.status === 422) {
                        const msg = json.message || 'Please fix the highlighted fields.';
                        if (errBox) { errBox.textContent = msg; errBox.classList.remove('d-none'); }
                        window.toastr?.error?.(msg);
                        return;
                    }
                    if (!res.ok || json.success === false) throw new Error(json.message || ('HTTP ' + res.status));

                    window.toastr?.success?.(json.message || '{{ __("auth._updated") }}');
                    window.StockList?.reload?.();
                    window.StockDrawer?.reload?.(id);

                } catch (err) {
                    console.error('[adjust-panel] submit failed:', err);
                    window.toastr?.error?.(err.message);
                    if (errBox) { errBox.textContent = err.message; errBox.classList.remove('d-none'); }
                } finally {
                    setLoading(btn, false);
                }
            });
        }

        return { open };
    })();

    /* ════════════════════════════════════════════════════════════
       StockTransferPanel — inline in the drawer
       ════════════════════════════════════════════════════════════ */
    window.StockTransferPanel = (function () {

        async function open(id) {
            activePanelId = id;

            const body = document.getElementById('stockDrawerContent');
            if (!body) return;
            body.classList.remove('d-none');
            body.innerHTML = `<div class="text-center py-8"><span class="spinner-border text-primary"></span></div>`;
            document.getElementById('stockDrawerFooter')?.classList.add('d-none');

            try {
                const data = await fetchDetails(id);
                render(data.item);
            } catch (err) {
                console.error('[transfer-panel] load failed:', err);
                body.innerHTML = `<div class="alert alert-danger">
                    <i class="bi bi-exclamation-triangle me-2"></i>${esc(err.message)}
                </div>`;
            }
        }

        function buildLocationOptions() {
            const src = document.getElementById('stockLocationFilter');
            if (!src) return '<option value="">{{ __("auth._select") }} {{ __("pagination._location") }}</option>';
            const opts = ['<option value="">{{ __("auth._select") }} {{ __("pagination._location") }}</option>'];
            Array.from(src.options).forEach(o => {
                if (!o.value) return;
                opts.push(`<option value="${esc(o.value)}">${esc(o.textContent.trim())}</option>`);
            });
            return opts.join('');
        }

        function buildDepartmentOptions() {
            const src = document.getElementById('stockDepartmentFilter');
            if (!src) return '<option value="">{{ __("auth._select") }} {{ __("auth._department") }}</option>';
            const opts = ['<option value="">{{ __("auth._select") }} {{ __("auth._department") }}</option>'];
            Array.from(src.options).forEach(o => {
                if (!o.value) return;
                opts.push(`<option value="${esc(o.value)}" data-location="${esc(o.getAttribute('data-location') || '')}">${esc(o.textContent.trim())}</option>`);
            });
            return opts.join('');
        }

        function render(item) {
            const current = Number(item.quantity_allocated || 0);

            const bodyHtml = `
                <div class="d-flex align-items-center gap-3 mb-4">
                    <img src="${productImageUrl(item.variant_image)}"
                         class="rounded-3 border" style="width:56px;height:56px;object-fit:cover;"
                         onerror="this.src='{{ asset('assets/media/stock/ecommerce/2.png') }}'">
                    <div>
                        <div class="fw-bold text-gray-800">${esc(item.variant_name || '—')}</div>
                        <div class="text-muted fs-7">
                            SKU <code>${esc(item.variant_sku || '—')}</code>
                            · From: ${esc(item.location_name || '—')} / ${esc(item.department_name || '—')}
                        </div>
                    </div>
                </div>

                <div id="transferError" class="alert alert-danger d-none" role="alert"></div>

                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('pagination._location') }}</label>
                    <select class="form-select form-select-solid" id="transferLocation" data-transfer-location>
                        ${buildLocationOptions()}
                    </select>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold">{{ __('auth._department') }}</label>
                    <select class="form-select form-select-solid" id="transferDepartment" data-transfer-department disabled>
                        ${buildDepartmentOptions()}
                    </select>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-md-5">
                        <label class="form-label fw-bold">{{ __('passwords.current_stock_level') }}</label>
                        <input type="text" class="form-control text-center fs-3 fw-bold text-warning"
                               id="transferCurrentStock" value="${current}" readonly>
                    </div>
                    <div class="col-md-7">
                        <label class="form-label fw-bold">{{ __('passwords.transfer_quantity') }}</label>
                        <input type="number" class="form-control text-center fs-3 fw-bold text-gray-800"
                               id="transferAmount" value="0"
                               data-item-id="${item.id}" data-current="${current}"
                               min="0" max="${current}">
                        <small class="text-muted">{{ __('passwords.transfer_hint') ?? 'Enter quantity to transfer' }}</small>
                    </div>
                </div>

                <div class="alert alert-primary d-flex align-items-center mt-3" role="alert">
                    <i class="bi bi-arrow-left-right fs-4 me-2"></i>
                    <div>
                        <span class="fw-bold">{{ __('passwords.transfer_summary') }}</span><br>
                        <span id="transferSummary">{{ __('passwords.no_transfer_yet') }}</span>
                    </div>
                </div>
            `;

            const footerHtml = `
                <button type="button" class="btn btn-sm btn-light" data-panel-back>{{ __('auth._discard') }}</button>
                <button type="button" class="btn btn-primary" data-transfer-submit data-item-id="${item.id}">
                    <span class="indicator-label">{{ __('auth._transfer') }}</span>
                    <span class="indicator-progress d-none">{{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            `;

            const body = panelShell('{{ __("passwords.transfer_stock") }}', bodyHtml, footerHtml);
            if (!body) return;

            bind(body, item.id);
        }

        function bind(root, id) {
            const locSel  = root.querySelector('[data-transfer-location]');
            const deptSel = root.querySelector('[data-transfer-department]');
            const amtIn   = root.querySelector('#transferAmount');

            locSel?.addEventListener('change', () => {
                const loc = String(locSel.value || '');
                Array.from(deptSel.options).forEach(opt => {
                    if (!opt.value) return;
                    const matches = !loc || String(opt.getAttribute('data-location')) === loc;
                    opt.hidden   = !matches;
                    opt.disabled = !matches;
                });
                deptSel.value = '';
                deptSel.disabled = !loc;
                recalc();
            });

            function recalc() {
                const current = parseInt(amtIn.dataset.current, 10) || 0;
                let v = parseInt(amtIn.value, 10);
                if (isNaN(v) || v < 0) v = 0;

                const curEl  = root.querySelector('#transferCurrentStock');
                const sumEl  = root.querySelector('#transferSummary');
                const errEl  = root.querySelector('#transferError');
                const subBtn = root.querySelector('[data-transfer-submit]');

                let error = null;
                if (v > current) error = `Cannot transfer more than available. Available: ${current}`;

                if (error) {
                    if (errEl) { errEl.textContent = error; errEl.classList.remove('d-none'); }
                    amtIn.classList.add('is-invalid');
                    if (subBtn) subBtn.disabled = true;
                } else {
                    if (errEl) { errEl.textContent = ''; errEl.classList.add('d-none'); }
                    amtIn.classList.remove('is-invalid');
                    if (subBtn) subBtn.disabled = false;
                }

                if (curEl) curEl.value = current - v;

                if (sumEl) {
                    if (v === 0) {
                        sumEl.textContent = '{{ __("passwords.no_transfer_yet") }}';
                    } else {
                        const locTxt  = locSel?.selectedOptions[0]?.textContent?.trim() || '—';
                        const deptTxt = deptSel?.selectedOptions[0]?.textContent?.trim() || '—';
                        sumEl.innerHTML = `
                            <span class="text-primary fw-bold">Transferring: ${v} units</span>
                            <span class="text-muted ms-2">| To: ${locTxt} / ${deptTxt} | Remaining: ${current - v}</span>
                        `;
                    }
                }
            }

            amtIn?.addEventListener('input', recalc);
            amtIn?.addEventListener('change', recalc);

            root.querySelector('[data-transfer-submit]')?.addEventListener('click', async (ev) => {
                const btn     = ev.currentTarget;
                const errBox  = root.querySelector('#transferError');

                const locId  = locSel?.value || '';
                const deptId = deptSel?.value || '';
                const amount = parseInt(amtIn.value, 10) || 0;
                const current = parseInt(amtIn.dataset.current, 10) || 0;

                function fail(msg) {
                    if (errBox) { errBox.textContent = msg; errBox.classList.remove('d-none'); }
                    window.toastr?.error?.(msg);
                }

                errBox?.classList.add('d-none');
                if (!locId) return fail('Please select a location.');
                if (!deptId) return fail('Please select a department.');
                if (amount < 1) return fail('Transfer quantity must be at least 1.');
                if (amount > current) return fail(`You cannot transfer more than ${current} unit(s).`);

                setLoading(btn, true);
                try {
                    const res = await fetch(URL_TRANSFER.replace('__ID__', id), {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN':     CSRF,
                            'Accept':           'application/json',
                            'Content-Type':     'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({
                            _method:                 'PUT', 
                            location_id:     parseInt(locId, 10),
                            department_id:   parseInt(deptId, 10),
                            transfer_amount: amount,
                        }),
                    });
                    const json = await res.json().catch(() => ({}));

                    if (res.status === 422) {
                        const msg = json.message || 'Please fix the highlighted fields.';
                        if (errBox) { errBox.textContent = msg; errBox.classList.remove('d-none'); }
                        window.toastr?.error?.(msg);
                        return;
                    }
                    if (!res.ok || json.success === false) throw new Error(json.message || ('HTTP ' + res.status));

                    window.toastr?.success?.(json.message || '{{ __("passwords.stock_transfer_success") }}');
                    window.StockList?.reload?.();
                    window.StockDrawer?.reload?.(id);

                } catch (err) {
                    console.error('[transfer-panel] submit failed:', err);
                    window.toastr?.error?.(err.message);
                    if (errBox) { errBox.textContent = err.message; errBox.classList.remove('d-none'); }
                } finally {
                    setLoading(btn, false);
                }
            });
        }

        return { open };
    })();
})();
</script>