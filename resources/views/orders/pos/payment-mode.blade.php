
<style>
    /* ── Large POS Amount Input ────────────────────────────── */
    .pm-amount-display {
        font-size: 2.6rem;
        font-weight: 800;
        letter-spacing: -1px;
        border: 2.5px solid #e4e6ef;
        border-radius: 12px;
        background: #f8f9ff;
        color: #1a1a2e;
        text-align: right;
        padding: 14px 20px 14px 52px;
        width: 100%;
        transition: border-color .2s, box-shadow .2s, background .2s;
        -moz-appearance: textfield;
    }
    .pm-amount-display::-webkit-inner-spin-button,
    .pm-amount-display::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
    .pm-amount-display:focus {
        outline: none;
        border-color: var(--bs-primary);
        box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), .12);
        background: #fff;
    }
    .pm-amount-display::placeholder { color: #c5cae0; }
    .pm-currency-prefix {
        position: absolute;
        top: 50%; left: 16px;
        transform: translateY(-50%);
        font-size: 1.5rem;
        font-weight: 800;
        color: var(--bs-primary);
        pointer-events: none;
        z-index: 2;
    }

    /* ── Quick preset buttons ──────────────────────────────── */
    .pm-quick-btn {
        font-size: .82rem;
        font-weight: 700;
        padding: 7px 13px;
        border-radius: 8px;
        border: 2px solid #e4e6ef;
        background: #fff;
        color: #3f4254;
        cursor: pointer;
        transition: all .15s;
        white-space: nowrap;
        line-height: 1.3;
    }
    .pm-quick-btn:hover {
        border-color: var(--bs-primary);
        color: var(--bs-primary);
        background: #eef3ff;
        transform: translateY(-1px);
    }
    .pm-quick-btn.pm-exact {
        border-color: #50cd89;
        color: #50cd89;
        background: #e8fff3;
    }
    .pm-quick-btn.pm-exact:hover {
        background: #50cd89;
        color: #fff;
        border-color: #50cd89;
    }

    /* ── Change / Tendered banners ─────────────────────────── */
    .pm-calc-box {
        border-radius: 12px;
        padding: 14px 18px;
    }
    .pm-calc-box .pm-calc-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .09em;
        text-transform: uppercase;
        margin-bottom: 3px;
    }
    .pm-calc-box .pm-calc-value {
        font-size: 1.9rem;
        font-weight: 800;
        line-height: 1;
    }
    .pm-tendered-box {
        background: #f0f4ff;
        border: 1.5px solid #d0d8ff;
    }
    .pm-tendered-box .pm-calc-label { color: #7e8299; }
    .pm-tendered-box .pm-calc-value { color: #1a1a2e; }
    .pm-change-box {
        background: linear-gradient(135deg, #1bc5bd, #0bb7af);
    }
    .pm-change-box .pm-calc-label { color: rgba(255,255,255,.8); }
    .pm-change-box .pm-calc-value { color: #fff; }
    .pm-change-box.pm-underpaid {
        background: linear-gradient(135deg, #f64e60, #ee2d41);
    }

    /* ── Summary strip ─────────────────────────────────────── */
    .pm-summary-tile {
        border-radius: 14px;
        padding: 16px 18px;
        text-align: center;
    }
    .pm-summary-tile .pm-tile-label {
        font-size: .68rem;
        font-weight: 700;
        letter-spacing: .08em;
        text-transform: uppercase;
        color: #7e8299;
        margin-bottom: 5px;
    }
    .pm-summary-tile .pm-tile-value {
        font-size: 1.8rem;
        font-weight: 800;
        line-height: 1;
    }
</style>

<!-- ═══════════════════════════════════════════════════════════
     MULTI-PAYMENT MODAL
═══════════════════════════════════════════════════════════ -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-1000px">
        <div class="modal-content shadow-lg">

            {{-- ── HEADER ──────────────────────────────────────── --}}
            <div class="modal-header bg-primary px-7 py-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="symbol symbol-45px symbol-circle bg-white bg-opacity-20">
                        <span class="symbol-label">
                            <i class="ki-duotone ki-wallet fs-2 text-white">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                        </span>
                    </div>
                    <div>
                        <h2 class="modal-title fw-bold text-white fs-2 mb-0">
                            {{ __('pagination.process_payment') }}
                        </h2>
                        <span class="text-white opacity-75 fs-7" id="pm-order-ref">—</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- ── BODY ─────────────────────────────────────────── --}}
            <div class="modal-body p-7">

                {{-- ORDER SUMMARY STRIP --}}
                <div class="row g-4 mb-7">
                    <div class="col-4">
                        <div class="pm-summary-tile bg-light-primary">
                            <div class="pm-tile-label">{{ __('pagination.order_total') }}</div>
                            <div class="pm-tile-value text-primary" id="pm-order-total">0.00</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="pm-summary-tile bg-light-success">
                            <div class="pm-tile-label">{{ __('pagination.paid_amount') }}</div>
                            <div class="pm-tile-value text-success" id="pm-paid-amount">0.00</div>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="pm-summary-tile bg-light-danger" id="pm-remaining-wrap">
                            <div class="pm-tile-label">{{ __('pagination.remaining_balance') }}</div>
                            <div class="pm-tile-value text-danger" id="pm-remaining">0.00</div>
                        </div>
                    </div>
                </div>

                {{-- ADD PAYMENT CARD --}}
                <div class="card card-flush border border-primary border-dashed mb-7">
                    <div class="card-header min-h-50px px-6 pt-5 pb-0 border-0">
                        <h4 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-plus-circle fs-3 text-primary me-2">
                                <span class="path1"></span><span class="path2"></span>
                            </i>
                            {{ __('pagination.add_payment') }}
                        </h4>
                    </div>
                    <div class="card-body pt-4 px-6 pb-6">

                        {{-- PAYMENT TYPE TABS --}}
                        <ul class="nav nav-stretch nav-line-tabs nav-line-tabs-2x border-transparent fs-6 fw-bold mb-5"
                            id="pm-type-tabs" role="tablist">
                            @foreach(getUniquePaymentTypes() as $type)
                            <li class="nav-item" role="presentation">
                                <button type="button"
                                        class="nav-link text-active-primary pb-3 {{ $loop->first ? 'active' : '' }}"
                                        id="pm-tab-{{ $type }}"
                                        data-bs-toggle="tab"
                                        data-bs-target="#pm-pane-{{ $type }}"
                                        role="tab"
                                        data-payment-type="{{ $type }}">
                                    <i class="ki-duotone {{ getPaymentTypeIcon($type) }} fs-3 me-2">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                    {{ ucfirst(str_replace('_', ' ', $type)) }}
                                </button>
                            </li>
                            @endforeach
                        </ul>

                        {{-- TAB PANES --}}
                        <div class="tab-content" id="pm-tab-content">
                            @foreach(getUniquePaymentTypes() as $type)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                 id="pm-pane-{{ $type }}"
                                 role="tabpanel"
                                 data-payment-type="{{ $type }}">

                                {{-- Account + Add button --}}
                                <div class="row g-4 mb-5 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold required">
                                            {{ __('pagination.select_account') }}
                                        </label>
                                        <select class="form-select form-select-solid pm-account-select"
                                                id="pm-account-{{ $type }}"
                                                data-payment-type="{{ $type }}">
                                            <option value="">{{ __('pagination.select_account') }}</option>
                                            @foreach(getPaymentMethodsByType($type) as $method)
                                            <option value="{{ $method->id }}"
                                                    data-account="{{ json_encode($method) }}">
                                                {{ $method->name }}
                                                @if($method->account_number) — {{ $method->account_number }} @endif
                                            </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button"
                                                class="btn btn-secondary w-100 h-45px pm-add-btn"
                                                id="pm-add-btn-{{ $type }}"
                                                data-payment-type="{{ $type }}"
                                                disabled>
                                            <i class="ki-duotone ki-plus fs-3">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            {{ __('pagination.add_payment') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- BIG Amount Input --}}
                                <div class="mb-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="form-label fw-bold fs-6 mb-0 required">
                                            {{ __('pagination.amount_tendered') }}
                                        </label>
                                        <span class="badge badge-light-primary fs-8 fw-semibold" id="pm-remaining-hint-{{ $type }}">
                                            {{ __('pagination.remaining') }}: —
                                        </span>
                                    </div>
                                    <div class="position-relative">
                                        <span class="pm-currency-prefix">{{ config('app.currency_symbol', '$') }}</span>
                                        <input type="number"
                                               class="pm-amount-display pm-amount-input"
                                               id="pm-amount-{{ $type }}"
                                               placeholder="0.00"
                                               step="0.01"
                                               min="0.01"
                                               autocomplete="off"
                                               data-payment-type="{{ $type }}">
                                    </div>
                                </div>

                                {{-- Quick preset amounts --}}
                                <div class="d-flex flex-wrap gap-2 mb-4" id="pm-quick-{{ $type }}"></div>

                                {{-- Live cash calc (cash type only) --}}
                                <div class="d-none" id="pm-cash-calc-{{ $type }}">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="pm-calc-box pm-tendered-box">
                                                <div class="pm-calc-label">{{ __('pagination.cash_tendered') }}</div>
                                                <div class="pm-calc-value" id="pm-tendered-{{ $type }}">
                                                    {{ config('app.currency_symbol', '$') }}0.00
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="pm-calc-box pm-change-box" id="pm-change-banner-{{ $type }}">
                                                <div class="pm-calc-label">{{ __('pagination.change_due') }}</div>
                                                <div class="pm-calc-value" id="pm-change-{{ $type }}">
                                                    {{ config('app.currency_symbol', '$') }}0.00
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Transaction Reference --}}
                                <div class="mt-4 d-none pm-ref-row" id="pm-ref-row-{{ $type }}">
                                    <label class="form-label fw-semibold">
                                        {{ __('pagination.transaction_reference') }}
                                        <span class="text-muted fw-normal ms-1">({{ __('pagination.optional') }})</span>
                                    </label>
                                    <input type="text"
                                           class="form-control form-control-solid pm-ref-input"
                                           id="pm-ref-{{ $type }}"
                                           placeholder="{{ __('pagination.enter_transaction_id') }}"
                                           data-payment-type="{{ $type }}">
                                    <div class="form-text">{{ __('pagination.transaction_id_help') }}</div>
                                </div>

                            </div>
                            @endforeach
                        </div>

                    </div>
                </div>

                {{-- SPLITS TABLE --}}
                <div class="card card-flush">
                    <div class="card-header min-h-50px px-6">
                        <h4 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-bill fs-3 text-success me-2">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span>
                            </i>
                            {{ __('pagination.payment_splits') }}
                        </h4>
                        <div class="card-toolbar">
                            <span class="badge badge-light-success fs-7 fw-bold" id="pm-splits-count">
                                0 {{ __('pagination.payments') }}
                            </span>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-row-dashed table-row-gray-300 align-middle gs-5 gy-3 mb-0">
                                <thead>
                                    <tr class="fw-bold text-muted bg-light fs-7 text-uppercase">
                                        <th class="ps-6">{{ __('pagination.payment_method') }}</th>
                                        <th>{{ __('pagination.account') }}</th>
                                        <th class="text-end">{{ __('pagination.tendered') }}</th>
                                        <th class="text-end text-success">{{ __('pagination.applied') }}</th>
                                        <th class="text-end text-info">{{ __('pagination.change') }}</th>
                                        <th class="text-end pe-6">{{ __('pagination.actions') }}</th>
                                    </tr>
                                </thead>
                                <tbody id="pm-splits-body">
                                    <tr>
                                        <td colspan="6" class="text-center py-12 text-muted">
                                            <i class="ki-duotone ki-wallet fs-3x mb-3 d-block opacity-20">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <div class="fs-6 fw-semibold">{{ __('pagination.no_payments_added') }}</div>
                                            <div class="fs-7 opacity-60 mt-1">{{ __('pagination.use_form_above_to_add') }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light-primary fw-bolder">
                                        <td colspan="2" class="text-end fs-6 ps-6 py-4 text-gray-700">
                                            {{ __('pagination.totals') }}:
                                        </td>
                                        <td class="text-end py-4 text-gray-600" id="pm-total-tendered">—</td>
                                        <td class="text-end fs-4 py-4 text-primary" id="pm-splits-total">0.00</td>
                                        <td class="text-end py-4 text-success" id="pm-total-change">—</td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>

            </div>{{-- /modal-body --}}

            {{-- ── FOOTER ──────────────────────────────────────── --}}
            <div class="modal-footer px-7 py-5">
                <button type="button" class="btn btn-light btn-lg me-3" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2 me-1">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ __('pagination.cancel') }}
                </button>
                <button type="button" class="btn btn-success btn-lg" id="pm-process-btn" disabled>
                    <span class="indicator-label">
                        <i class="ki-duotone ki-check fs-2 me-1">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        {{ __('pagination.complete_payment') }}
                    </span>
                    <span class="indicator-progress">
                        {{ __('pagination.processing') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>

        </div>
    </div>
</div>

{{-- Receipt Modal --}}
<div class="modal fade" id="receiptModal" tabindex="-1"
     aria-labelledby="receiptModalLabel" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
</div>
<div id="printReceiptContainer" style="display:none;"></div>


{{-- ═══════════════════════════════════════════════════════════
     JAVASCRIPT
     Fully IIFE-scoped. All DOM lookups inside handlers = always live.
═══════════════════════════════════════════════════════════ --}}
<script>
(function () {

    // ── BOOTSTRAP CONFIG ─────────────────────────────────────
    @if(isset($active_payment_methods))
        window.activePaymentMethods = @json($globalPaymentMethods ?? []);
    @endif

    const SYM            = '{{ config('app.currency_symbol', '$') }}';
    const TYPES_WITH_REF = ['card', 'bank_account', 'mobile_money', 'digital_wallet', 'check'];
    const CASH_TYPES     = ['cash'];

    // ── STATE ────────────────────────────────────────────────
    let splitPayments = [];
    let currentOrder  = null;

    // ── TINY HELPERS ─────────────────────────────────────────
    const g   = id  => document.getElementById(id);
    const qs  = sel => document.querySelector(sel);

    function fmt(n) {
        return SYM + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    function getRemainingRaw() {
        const el = g('pm-remaining');
        return el ? parseFloat(el.textContent.replace(/[^0-9.-]+/g, '')) || 0 : 0;
    }

    // ── REMAINING HINT ───────────────────────────────────────
    function updateRemainingHint(type) {
        const hint = g(`pm-remaining-hint-${type}`);
        if (hint) hint.textContent = `{{ __('pagination.remaining') }}: ${fmt(getRemainingRaw())}`;
    }

    // ── QUICK AMOUNT PRESETS ─────────────────────────────────
    function buildQuickAmounts(type) {
        const container = g(`pm-quick-${type}`);
        if (!container) return;

        const remaining = getRemainingRaw();

        if (remaining <= 0) {
            container.innerHTML = `<span class="text-success fw-semibold fs-7">
                <i class="ki-duotone ki-check-circle fs-4 me-1 text-success"><span class="path1"></span><span class="path2"></span></i>
                {{ __('pagination.fully_paid') }}
            </span>`;
            return;
        }

        // Build smart preset list
        const presets = new Set([parseFloat(remaining.toFixed(2))]);
        const rounds  = [1, 2, 5, 10, 20, 50, 100, 200, 500, 1000, 2000, 5000, 10000];
        for (const r of rounds) {
            const rounded = Math.ceil(remaining / r) * r;
            if (rounded > remaining && rounded <= remaining * 5) presets.add(rounded);
            if (presets.size >= 7) break;
        }

        const sorted = [...presets].sort((a, b) => a - b).slice(0, 7);

        container.innerHTML = sorted.map(v => {
            const isExact = Math.abs(v - remaining) < 0.005;
            return `<button type="button"
                        class="pm-quick-btn ${isExact ? 'pm-exact' : ''}"
                        data-payment-type="${type}"
                        data-quick-amount="${v}">
                        ${isExact ? '<span style="font-size:.7rem">✓ EXACT</span><br>' : ''}${fmt(v)}
                    </button>`;
        }).join('');
    }

    // ── LIVE CASH CHANGE CALCULATOR ──────────────────────────
    function updateCashCalc(type) {
        const amountEl = g(`pm-amount-${type}`);
        const calcWrap = g(`pm-cash-calc-${type}`);
        if (!amountEl || !calcWrap) return;

        const isCash   = CASH_TYPES.includes(type);
        const tendered = parseFloat(amountEl.value) || 0;
        const remaining = getRemainingRaw();

        if (!isCash || tendered <= 0) {
            calcWrap.classList.add('d-none');
            return;
        }

        calcWrap.classList.remove('d-none');

        const change      = Math.max(0, tendered - remaining);
        const isUnderpaid = tendered < remaining - 0.005;
        const banner      = g(`pm-change-banner-${type}`);
        const tenderedEl  = g(`pm-tendered-${type}`);
        const changeEl    = g(`pm-change-${type}`);

        if (tenderedEl) tenderedEl.textContent = fmt(tendered);

        if (banner) {
            banner.classList.toggle('pm-underpaid', isUnderpaid);
        }

        if (changeEl) {
            changeEl.textContent = isUnderpaid
                ? `Short ${fmt(remaining - tendered)}`
                : fmt(change);
        }
    }

    // ── VALIDATE ADD BUTTON ──────────────────────────────────
    function validateBtn(type) {
        const account = g(`pm-account-${type}`);
        const amount  = g(`pm-amount-${type}`);
        const btn     = g(`pm-add-btn-${type}`);
        if (!account || !amount || !btn) return;

        const tendered  = parseFloat(amount.value) || 0;
        const remaining = getRemainingRaw();
        const isCash    = CASH_TYPES.includes(type);

        // Cash allows overpayment (change returned); all others must be <= remaining
        const ok = account.value !== ''
                && tendered > 0
                && (isCash ? remaining > 0 : tendered <= remaining + 0.005);

        btn.disabled = !ok;
        btn.classList.toggle('btn-primary',   ok);
        btn.classList.toggle('btn-secondary', !ok);
    }

    // ── TOGGLE TRANSACTION REF ───────────────────────────────
    function toggleRef(type) {
        const row = g(`pm-ref-row-${type}`);
        if (!row) return;
        const show = TYPES_WITH_REF.includes(type);
        row.classList.toggle('d-none', !show);
        if (!show) { const inp = g(`pm-ref-${type}`); if (inp) inp.value = ''; }
    }

    // ── RESET A TAB'S INPUTS ─────────────────────────────────
    function resetTab(type) {
        const els = [
            g(`pm-account-${type}`),
            g(`pm-amount-${type}`),
            g(`pm-ref-${type}`)
        ];
        els.forEach(el => { if (el) el.value = ''; });
        const calc = g(`pm-cash-calc-${type}`);
        if (calc) calc.classList.add('d-none');
        validateBtn(type);
        buildQuickAmounts(type);
        updateRemainingHint(type);
    }

    // ── RENDER SPLITS TABLE ──────────────────────────────────
    function renderTable() {
        const tbody = g('pm-splits-body');
        if (!tbody) return;

        if (!splitPayments.length) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="6" class="text-center py-12 text-muted">
                        <i class="ki-duotone ki-wallet fs-3x mb-3 d-block opacity-20">
                            <span class="path1"></span><span class="path2"></span>
                        </i>
                        <div class="fs-6 fw-semibold">{{ __('pagination.no_payments_added') }}</div>
                        <div class="fs-7 opacity-60 mt-1">{{ __('pagination.use_form_above_to_add') }}</div>
                    </td>
                </tr>`;
        } else {
            tbody.innerHTML = splitPayments.map((p, i) => `
                <tr>
                    <td class="ps-6">
                        <div class="d-flex align-items-center gap-3">
                            <span class="symbol symbol-35px symbol-circle">
                                <span class="symbol-label bg-light-primary">
                                    <i class="ki-duotone ${getPaymentTypeIcon(p.type)} fs-3 text-primary">
                                        <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                                    </i>
                                </span>
                            </span>
                            <div>
                                <div class="fw-bold text-gray-800">${formatPaymentType(p.type)}</div>
                                <small class="text-muted">${p.method_name}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="badge badge-light-primary fs-8">${p.account_number || 'N/A'}</span>
                        ${p.transaction_ref
                            ? `<small class="d-block text-muted mt-1">Ref: ${p.transaction_ref}</small>`
                            : ''}
                    </td>
                    <td class="text-end fw-bold text-gray-600">${fmt(p.tendered)}</td>
                    <td class="text-end fw-bolder fs-5 text-gray-900">${fmt(p.amount)}</td>
                    <td class="text-end">
                        ${p.change > 0.005
                            ? `<span class="badge badge-light-success fw-bold fs-7">${fmt(p.change)}</span>`
                            : '<span class="text-muted fs-7">—</span>'}
                    </td>
                    <td class="text-end pe-6">
                        <button type="button"
                                class="btn btn-sm btn-icon btn-light-danger pm-remove-btn"
                                data-index="${i}">
                            <i class="ki-duotone ki-trash fs-3">
                                <span class="path1"></span><span class="path2"></span>
                                <span class="path3"></span><span class="path4"></span><span class="path5"></span>
                            </i>
                        </button>
                    </td>
                </tr>`).join('');
        }

        // Foot counts
        const count = g('pm-splits-count');
        if (count) count.textContent = `${splitPayments.length} {{ __('pagination.payments') }}`;
    }

    // ── UPDATE SUMMARY ───────────────────────────────────────
    function updateSummary() {
        if (!currentOrder) return;

        const totalApplied  = splitPayments.reduce((s, p) => s + p.amount,   0);
        const totalTendered = splitPayments.reduce((s, p) => s + p.tendered, 0);
        const totalChange   = splitPayments.reduce((s, p) => s + p.change,   0);
        const remaining     = Math.max(0, currentOrder.total - totalApplied);

        // Summary strip
        g('pm-paid-amount').textContent = fmt(totalApplied);
        g('pm-remaining').textContent   = fmt(remaining);

        // Remaining tile colour
        const wrap  = g('pm-remaining-wrap');
        const remEl = g('pm-remaining');
        if (wrap) {
            wrap.classList.toggle('bg-light-danger',  remaining > 0.005);
            wrap.classList.toggle('bg-light-success', remaining <= 0.005);
        }
        if (remEl) {
            remEl.classList.toggle('text-danger',  remaining > 0.005);
            remEl.classList.toggle('text-success', remaining <= 0.005);
        }

        // Table foot
        const totTen = g('pm-total-tendered');
        const totApp = g('pm-splits-total');
        const totChg = g('pm-total-change');
        if (totTen) totTen.textContent = totalTendered > 0 ? fmt(totalTendered) : '—';
        if (totApp) totApp.textContent = fmt(totalApplied);
        if (totChg) totChg.textContent = totalChange > 0.005 ? fmt(totalChange) : '—';

        // Process button
        const pb = g('pm-process-btn');
        if (pb) pb.disabled = !(splitPayments.length > 0 && remaining <= 0.005);

        // Refresh active tab
        const activePane = qs('.tab-pane.active[data-payment-type]');
        if (activePane) {
            const t = activePane.dataset.paymentType;
            validateBtn(t);
            buildQuickAmounts(t);
            updateRemainingHint(t);
        }
    }

    // ── ADD PAYMENT ──────────────────────────────────────────
    function addPayment(type) {
        const accountEl = g(`pm-account-${type}`);
        const amountEl  = g(`pm-amount-${type}`);
        const refEl     = g(`pm-ref-${type}`);
        if (!accountEl || !amountEl) return;

        const accountId = accountEl.value;
        const tendered  = parseFloat(amountEl.value);
        const ref       = refEl ? refEl.value.trim() : '';
        const remaining = getRemainingRaw();
        const isCash    = CASH_TYPES.includes(type);

        if (!accountId) { toastr.warning('{{ __("pagination.please_select_account") }}'); return; }
        if (!tendered || tendered <= 0) { toastr.warning('{{ __("pagination.please_enter_valid_amount") }}'); return; }
        if (!isCash && tendered > remaining + 0.005) { toastr.warning('{{ __("pagination.amount_exceeds_remaining") }}'); return; }
        if (remaining <= 0) { toastr.warning('{{ __("pagination.order_already_paid") }}'); return; }

        const applied = isCash ? Math.min(tendered, remaining) : tendered;
        const change  = isCash ? Math.max(0, tendered - remaining) : 0;

        const opt = accountEl.options[accountEl.selectedIndex];
        let accountData = {};
        try { accountData = JSON.parse(opt.dataset.account || '{}'); } catch(e) {}

        splitPayments.push({
            id:             Date.now() + Math.random(),
            type,
            method_id:      accountId,
            method_name:    accountData.name            || 'Unknown',
            account_number: accountData.account_number  || '',
            tendered,
            amount:         applied,
            change,
            transaction_ref: ref,
            account_data:   accountData
        });

        renderTable();
        updateSummary();
        resetTab(type);

        if (change > 0.005) {
            toastr.info(
                `<strong>{{ __('pagination.change_due') }}: ${fmt(change)}</strong>`,
                '{{ __("pagination.give_change_to_customer") }}',
                { timeOut: 6000, extendedTimeOut: 3000 }
            );
        }

        if (getRemainingRaw() <= 0.005) {
            toastr.success('{{ __("pagination.payment_complete") }}');
        }
    }

    // ── REMOVE PAYMENT ───────────────────────────────────────
    function removePayment(index) {
        splitPayments.splice(index, 1);
        renderTable();
        updateSummary();
    }

    // ── OPEN MODAL ───────────────────────────────────────────
    window.openPaymentModal = function (cartData) {
        currentOrder        = cartData;
        window.currentOrder = cartData;
        splitPayments       = [];

        g('pm-order-total').textContent = fmt(cartData.total);
        g('pm-paid-amount').textContent = fmt(0);
        g('pm-remaining').textContent   = fmt(cartData.total);

        const refEl = g('pm-order-ref');
        if (refEl) refEl.textContent = cartData.ref ? `#${cartData.ref}` : '—';

        // Full reset
        document.querySelectorAll('.pm-account-select').forEach(s => s.value = '');
        document.querySelectorAll('.pm-amount-input').forEach(i => i.value = '');
        document.querySelectorAll('.pm-ref-input').forEach(i => i.value = '');
        document.querySelectorAll('.pm-add-btn').forEach(b => {
            b.disabled = true;
            b.classList.remove('btn-primary');
            b.classList.add('btn-secondary');
        });
        document.querySelectorAll('[id^="pm-cash-calc-"]').forEach(el => el.classList.add('d-none'));

        const pb = g('pm-process-btn');
        if (pb) pb.disabled = true;

        const wrap  = g('pm-remaining-wrap');
        const remEl = g('pm-remaining');
        if (wrap)  { wrap.classList.add('bg-light-danger');  wrap.classList.remove('bg-light-success'); }
        if (remEl) { remEl.classList.add('text-danger');     remEl.classList.remove('text-success'); }

        renderTable();

        setTimeout(() => {
            const activePane = qs('.tab-pane.active[data-payment-type]');
            if (activePane) {
                const t = activePane.dataset.paymentType;
                toggleRef(t);
                buildQuickAmounts(t);
                updateRemainingHint(t);
            }
        }, 150);

        bootstrap.Modal.getOrCreateInstance(g('paymentModal')).show();
    };

    // ── PROCESS PAYMENT ──────────────────────────────────────
    window.processSplitPayments = function () {
        if (!currentOrder)        { toastr.error('{{ __("pagination.no_order_found") }}');   return; }
        if (!splitPayments.length){ toastr.warning('{{ __("pagination.no_payments_added") }}'); return; }

        const totalApplied = splitPayments.reduce((s, p) => s + p.amount, 0);
        if (Math.abs(currentOrder.total - totalApplied) > 0.01) {
            toastr.warning('{{ __("pagination.payment_total_mismatch") }}');
            return;
        }

        const btn = g('pm-process-btn');
        btn.setAttribute('data-kt-indicator', 'on');
        btn.disabled = true;

        const payload = {
            ...currentOrder,
            payments: splitPayments.map(p => ({
                payment_method_id:     p.method_id,
                amount:                p.amount,
                tendered:              p.tendered,
                change:                p.change,
                transaction_reference: p.transaction_ref,
                type:                  p.type,
                method_name:           p.method_name,
                account_number:        p.account_number
            })),
            total_paid:      totalApplied,
            total_tendered:  splitPayments.reduce((s, p) => s + p.tendered, 0),
            total_change:    splitPayments.reduce((s, p) => s + p.change,   0),
            payment_methods: splitPayments.map(p => p.type).join(', ')
        };

        fetch('/orders/process-split-payment', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;
            if (data.success) {
                toastr.success('{{ __("pagination.payment_completed") }}');
                bootstrap.Modal.getInstance(g('paymentModal')).hide();
                if (typeof generateMultiPaymentReceipt === 'function') generateMultiPaymentReceipt(payload);
                if (typeof clearCart === 'function') clearCart();
            } else {
                toastr.error(data.message || '{{ __("pagination.payment_failed") }}');
            }
        })
        .catch(err => {
            btn.removeAttribute('data-kt-indicator');
            btn.disabled = false;
            toastr.error('{{ __("pagination.payment_error") }}');
            console.error('Payment error:', err);
        });
    };

    // ══════════════════════════════════════════════════════════
    // DELEGATED EVENT LISTENERS
    // All bound on document — survives any modal re-render
    // ══════════════════════════════════════════════════════════

    document.addEventListener('click', function (e) {
        // Quick preset
        const qb = e.target.closest('.pm-quick-btn');
        if (qb) {
            const type = qb.dataset.paymentType;
            const inp  = g(`pm-amount-${type}`);
            if (inp) {
                inp.value = qb.dataset.quickAmount;
                inp.dispatchEvent(new Event('input', { bubbles: true }));
                inp.focus();
            }
            return;
        }

        // Add payment
        const addBtn = e.target.closest('.pm-add-btn');
        if (addBtn && !addBtn.disabled) {
            addPayment(addBtn.dataset.paymentType);
            return;
        }

        // Remove payment
        const remBtn = e.target.closest('.pm-remove-btn');
        if (remBtn) {
            removePayment(parseInt(remBtn.dataset.index, 10));
            return;
        }

        // Process button
        if (e.target.closest('#pm-process-btn')) {
            window.processSplitPayments();
        }
    });

    document.addEventListener('change', function (e) {
        const sel = e.target.closest('.pm-account-select');
        if (sel) validateBtn(sel.dataset.paymentType);
    });

    document.addEventListener('input', function (e) {
        const inp = e.target.closest('.pm-amount-input');
        if (!inp) return;
        const type = inp.dataset.paymentType;
        validateBtn(type);
        updateCashCalc(type);
    });

    document.addEventListener('shown.bs.tab', function (e) {
        const type = e.target.dataset.paymentType;
        if (!type) return;
        toggleRef(type);
        buildQuickAmounts(type);
        validateBtn(type);
        updateRemainingHint(type);
    });

})();
</script>