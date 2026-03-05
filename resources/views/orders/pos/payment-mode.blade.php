{{--
════════════════════════════════════════════════════════════════
  payment-modals.blade.php  —  HTML + CSS only, zero JavaScript
  Include order in your layout:

    @include('partials.payment-modals')   ← HTML structures
    @include('partials.payment-scripts')  ← all JavaScript
════════════════════════════════════════════════════════════════
--}}

{{-- Barcode font --}}
<link href="https://fonts.googleapis.com/css2?family=Libre+Barcode+128&display=swap" rel="stylesheet">

<style>
/* ═══════════════════════════════════════════════════════════
   PAYMENT MODAL STYLES
═══════════════════════════════════════════════════════════ */
.pm-amount-display {
    font-size: 2.6rem; font-weight: 800; letter-spacing: -1px;
    border: 2.5px solid #e4e6ef; border-radius: 12px;
    background: #f8f9ff; color: #1a1a2e; text-align: right;
    padding: 14px 20px 14px 52px; width: 100%;
    transition: border-color .2s, box-shadow .2s, background .2s;
    -moz-appearance: textfield;
}
.pm-amount-display::-webkit-inner-spin-button,
.pm-amount-display::-webkit-outer-spin-button { -webkit-appearance: none; margin: 0; }
.pm-amount-display:focus {
    outline: none; border-color: var(--bs-primary);
    box-shadow: 0 0 0 4px rgba(var(--bs-primary-rgb), .12); background: #fff;
}
.pm-amount-display::placeholder { color: #c5cae0; }

.pm-currency-prefix {
    position: absolute; top: 50%; left: 16px; transform: translateY(-50%);
    font-size: 1.5rem; font-weight: 800; color: var(--bs-primary);
    pointer-events: none; z-index: 2;
}

.pm-quick-btn {
    font-size: .82rem; font-weight: 700; padding: 7px 13px; border-radius: 8px;
    border: 2px solid #e4e6ef; background: #fff; color: #3f4254;
    cursor: pointer; transition: all .15s; white-space: nowrap; line-height: 1.3;
}
.pm-quick-btn:hover { border-color: var(--bs-primary); color: var(--bs-primary); background: #eef3ff; transform: translateY(-1px); }
.pm-quick-btn.pm-exact { border-color: #50cd89; color: #50cd89; background: #e8fff3; }
.pm-quick-btn.pm-exact:hover { background: #50cd89; color: #fff; }

.pm-calc-box { border-radius: 12px; padding: 14px 18px; }
.pm-calc-box .pm-calc-label { font-size: .68rem; font-weight: 700; letter-spacing: .09em; text-transform: uppercase; margin-bottom: 3px; }
.pm-calc-box .pm-calc-value { font-size: 1.9rem; font-weight: 800; line-height: 1; }
.pm-tendered-box { background: #f0f4ff; border: 1.5px solid #d0d8ff; }
.pm-tendered-box .pm-calc-label { color: #7e8299; }
.pm-tendered-box .pm-calc-value { color: #1a1a2e; }
.pm-change-box { background: linear-gradient(135deg, #1bc5bd, #0bb7af); }
.pm-change-box .pm-calc-label { color: rgba(255,255,255,.8); }
.pm-change-box .pm-calc-value { color: #fff; }
.pm-change-box.pm-underpaid { background: linear-gradient(135deg, #f64e60, #ee2d41); }

.pm-summary-tile { border-radius: 14px; padding: 16px 18px; text-align: center; }
.pm-summary-tile .pm-tile-label { font-size: .68rem; font-weight: 700; letter-spacing: .08em; text-transform: uppercase; color: #7e8299; margin-bottom: 5px; }
.pm-summary-tile .pm-tile-value { font-size: 1.8rem; font-weight: 800; line-height: 1; }

</style>


{{-- ═══════════════════════════════════════════════════════
     MODAL 1 — PAYMENT
═══════════════════════════════════════════════════════ --}}
<div class="modal fade" id="paymentModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered mw-1000px">
        <div class="modal-content shadow-lg">

            {{-- Header --}}
            <div class="modal-header bg-primary px-7 py-5">
                <div class="d-flex align-items-center gap-3">
                    <div class="symbol symbol-45px symbol-circle bg-white bg-opacity-20">
                        <span class="symbol-label">
                            <i class="ki-duotone ki-wallet fs-2 text-white"><span class="path1"></span><span class="path2"></span></i>
                        </span>
                    </div>
                    <div>
                        <h2 class="modal-title fw-bold text-white fs-2 mb-0">{{ __('pagination.process_payment') }}</h2>
                        <span class="text-white opacity-75 fs-7" id="pm-order-ref">—</span>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>

            {{-- Body --}}
            <div class="modal-body p-7">

                {{-- Order summary strip --}}
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

                {{-- Add payment card --}}
                <div class="card card-flush border border-primary border-dashed mb-7">
                    <div class="card-header min-h-50px px-6 pt-5 pb-0 border-0">
                        <h4 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-plus-circle fs-3 text-primary me-2"><span class="path1"></span><span class="path2"></span></i>
                            {{ __('pagination.add_payment') }}
                        </h4>
                    </div>
                    <div class="card-body pt-4 px-6 pb-6">

                        {{-- Payment type tabs --}}
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

                        {{-- Tab panes --}}
                        <div class="tab-content" id="pm-tab-content">
                            @foreach(getUniquePaymentTypes() as $type)
                            <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}"
                                 id="pm-pane-{{ $type }}" role="tabpanel" data-payment-type="{{ $type }}">

                                {{-- Account select + Add button --}}
                                <div class="row g-4 mb-5 align-items-end">
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold required">{{ __('pagination.select_account') }}</label>
                                        <select class="form-select form-select-solid pm-account-select"
                                                id="pm-account-{{ $type }}" data-payment-type="{{ $type }}">
                                            <option value="">{{ __('pagination.select_account') }}</option>
                                            @foreach(getPaymentMethodsByType($type) as $method)
                                            <option value="{{ $method->id }}" data-account="{{ json_encode($method) }}">
                                                {{ $method->name }}@if($method->account_number) — {{ $method->account_number }}@endif
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
                                            <i class="ki-duotone ki-plus fs-3"><span class="path1"></span><span class="path2"></span></i>
                                            {{ __('pagination.add_payment') }}
                                        </button>
                                    </div>
                                </div>

                                {{-- Amount input --}}
                                <div class="mb-4">
                                    <div class="d-flex align-items-center justify-content-between mb-2">
                                        <label class="form-label fw-bold fs-6 mb-0 required">{{ __('pagination.amount_tendered') }}</label>
                                        <span class="badge badge-light-primary fs-8 fw-semibold" id="pm-remaining-hint-{{ $type }}">
                                            {{ __('pagination.remaining') }}: —
                                        </span>
                                    </div>
                                    <div class="position-relative">
                                        <span class="pm-currency-prefix">{{ currency_symbol() }}</span>
                                        <input type="number"
                                               class="pm-amount-display pm-amount-input"
                                               id="pm-amount-{{ $type }}"
                                               placeholder="0.00"
                                               step="0.01" min="0.01" autocomplete="off"
                                               data-payment-type="{{ $type }}">
                                    </div>
                                </div>

                                {{-- Quick amount presets --}}
                                <div class="d-flex flex-wrap gap-2 mb-4" id="pm-quick-{{ $type }}"></div>

                                {{-- Cash change calculator --}}
                                <div class="d-none" id="pm-cash-calc-{{ $type }}">
                                    <div class="row g-3">
                                        <div class="col-6">
                                            <div class="pm-calc-box pm-tendered-box">
                                                <div class="pm-calc-label">{{ __('pagination.cash_tendered') }}</div>
                                                <div class="pm-calc-value" id="pm-tendered-{{ $type }}">{{ currency_symbol() }}0.00</div>
                                            </div>
                                        </div>
                                        <div class="col-6">
                                            <div class="pm-calc-box pm-change-box" id="pm-change-banner-{{ $type }}">
                                                <div class="pm-calc-label">{{ __('pagination.change_due') }}</div>
                                                <div class="pm-calc-value" id="pm-change-{{ $type }}">{{ currency_symbol() }}0.00</div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Transaction reference --}}
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

                {{-- Payment splits table --}}
                <div class="card card-flush">
                    <div class="card-header min-h-50px px-6">
                        <h4 class="card-title fw-bold text-gray-800">
                            <i class="ki-duotone ki-bill fs-3 text-success me-2">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span>
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
                                            <i class="ki-duotone ki-wallet fs-3x mb-3 d-block opacity-20"><span class="path1"></span><span class="path2"></span></i>
                                            <div class="fs-6 fw-semibold">{{ __('pagination.no_payments_added') }}</div>
                                        </td>
                                    </tr>
                                </tbody>
                                <tfoot>
                                    <tr class="bg-light-primary fw-bolder">
                                        <td colspan="2" class="text-end fs-6 ps-6 py-4 text-gray-700">{{ __('pagination.totals') }}:</td>
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

            {{-- Footer --}}
            <div class="modal-footer px-7 py-5">
                <button type="button" class="btn btn-light btn-lg me-3" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
                    {{ __('pagination.cancel') }}
                </button>
                <button type="button" class="btn btn-success btn-lg" id="pm-process-btn" disabled>
                    <span class="indicator-label">
                        <i class="ki-duotone ki-check fs-2 me-1"><span class="path1"></span><span class="path2"></span></i>
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





{{-- ═══════════════════════════════════════════════════════
     MODAL 2 — RECEIPT (METRONIC STYLE)
═══════════════════════════════════════════════════════ --}}
<style>
    /* ── Receipt Modal (Metronic Style) ───────────────────────── */
    #receiptModal .modal-dialog { 
        max-width: 450px; 
        margin: 1.75rem auto;
    }
    #receiptModal .modal-content { 
        border-radius: 0.85rem; 
        border: none; 
        box-shadow: var(--bs-box-shadow-lg);
        overflow: hidden;
    }

    /* ── Receipt Paper Style ──────────────────────────────────── */
    #receipt-paper {
        background: #ffffff;
        font-family: 'Inter', 'Poppins', system-ui, -apple-system, 'Segoe UI', Roboto, sans-serif;
        font-size: 13px; 
        color: #1e2129;
        padding: 25px 25px 20px;
        position: relative;
        max-height: 80vh; 
        overflow-y: auto;
    }

    /* ── Store Header ─────────────────────────────────────────── */
    .rcpt-store-name { 
        font-size: 22px; 
        font-weight: 700; 
        letter-spacing: -0.3px; 
        text-align: center; 
        color: var(--bs-gray-900);
        margin-bottom: 2px;
    }
    .rcpt-store-tagline { 
        font-size: 11px; 
        text-align: center; 
        color: var(--bs-gray-600);
        font-weight: 500;
        margin-bottom: 8px;
    }
    .rcpt-store-address { 
        font-size: 11px; 
        text-align: center; 
        color: var(--bs-gray-600); 
        line-height: 1.5; 
        font-weight: 400;
    }
    
    /* ── Dividers ─────────────────────────────────────────────── */
    .rcpt-divider-solid { 
        border-top: 1px solid var(--bs-gray-300); 
        margin: 15px 0; 
    }
    .rcpt-divider-dash  { 
        border-top: 1px dashed var(--bs-gray-400); 
        margin: 15px 0; 
    }
    .rcpt-divider-dot  { 
        border-top: 1px dotted var(--bs-gray-400); 
        margin: 12px 0; 
    }
    .rcpt-divider-star  { 
        text-align: center; 
        font-size: 12px; 
        color: var(--bs-gray-500); 
        margin: 12px 0; 
        letter-spacing: 2px; 
    }

    /* ── Meta Information ─────────────────────────────────────── */
    .rcpt-meta { 
        font-size: 12px; 
        color: var(--bs-gray-700); 
        line-height: 1.8; 
    }
    .rcpt-meta-row { 
        display: flex; 
        justify-content: space-between; 
        padding: 2px 0;
    }
    .rcpt-meta-row span:last-child { 
        font-weight: 600; 
        color: var(--bs-gray-900); 
    }

    /* ── Customer Banner ──────────────────────────────────────── */
    .rcpt-customer { 
        background: var(--bs-primary-light); 
        color: var(--bs-primary); 
        text-align: center; 
        padding: 8px 12px; 
        margin: 12px 0; 
        font-size: 13px; 
        font-weight: 600; 
        border-radius: 0.475rem;
        border-left: 4px solid var(--bs-primary);
    }

    /* ── Order Type Badge ─────────────────────────────────────── */
    .rcpt-order-type { 
        font-size: 11px; 
        font-weight: 600; 
        letter-spacing: 0.5px; 
        text-transform: uppercase; 
        background: var(--bs-light);
        padding: 4px 12px; 
        display: inline-block;
        color: var(--bs-gray-800);
        border-radius: 30px;
    }

    /* ── Items Table ──────────────────────────────────────────── */
    .rcpt-items { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 10px 0;
    }
    .rcpt-items th { 
        font-size: 11px; 
        font-weight: 600; 
        color: var(--bs-gray-600); 
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding-bottom: 6px;
        border-bottom: 1px solid var(--bs-gray-300);
    }
    .rcpt-items td { 
        padding: 6px 0; 
        vertical-align: top; 
        font-size: 13px; 
        border-bottom: 1px dashed var(--bs-gray-200);
    }
    .rcpt-item-qty { 
        width: 35px; 
        color: var(--bs-gray-700); 
        font-weight: 500;
    }
    .rcpt-item-name { 
        font-weight: 500;
        color: var(--bs-gray-900);
    }
    .rcpt-item-price { 
        text-align: right; 
        font-weight: 600; 
        white-space: nowrap; 
        padding-left: 10px; 
        color: var(--bs-gray-900);
    }
    .rcpt-item-sub { 
        font-size: 11px; 
        color: var(--bs-gray-600); 
        padding-left: 35px; 
        font-style: italic;
        border-bottom: none !important;
    }

    /* ── Totals Table ─────────────────────────────────────────── */
    .rcpt-totals { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 10px 0;
    }
    .rcpt-totals td { 
        padding: 4px 0; 
        font-size: 13px; 
    }
    .rcpt-total-label { 
        color: var(--bs-gray-700); 
        font-weight: 500;
    }
    .rcpt-total-value { 
        text-align: right; 
        font-weight: 600; 
        color: var(--bs-gray-900);
    }
    .rcpt-grand-row td { 
        font-size: 18px; 
        font-weight: 700; 
        padding-top: 8px; 
        padding-bottom: 4px; 
        color: var(--bs-primary);
        border-top: 1px solid var(--bs-gray-300);
    }
    .rcpt-grand-value { 
        text-align: right; 
        color: var(--bs-primary);
        font-weight: 700;
    }

    /* ── Payments Table ───────────────────────────────────────── */
    .rcpt-payments { 
        width: 100%; 
        border-collapse: collapse; 
        margin: 8px 0;
    }
    .rcpt-payments td { 
        padding: 4px 0; 
        font-size: 12px; 
        border-bottom: 1px dashed var(--bs-gray-200);
    }
    .rcpt-pay-label { 
        color: var(--bs-gray-700); 
        font-weight: 500;
    }
    .rcpt-pay-value { 
        text-align: right; 
        font-weight: 600; 
        color: var(--bs-gray-900);
    }

    /* ── Change Box ───────────────────────────────────────────── */
    .rcpt-change-box { 
        background: var(--bs-success-light); 
        text-align: center; 
        padding: 12px 15px; 
        margin: 15px 0; 
        border-radius: 0.625rem;
        border-left: 4px solid var(--bs-success);
    }
    .rcpt-change-label { 
        font-size: 11px; 
        letter-spacing: 0.5px; 
        text-transform: uppercase; 
        color: var(--bs-success); 
        font-weight: 600;
        margin-bottom: 4px;
    }
    .rcpt-change-value { 
        font-size: 28px; 
        font-weight: 700; 
        color: var(--bs-success);
        line-height: 1.2;
    }

    /* ── Thank You Section ────────────────────────────────────── */
    .rcpt-thankyou { 
        text-align: center; 
        font-size: 15px; 
        font-weight: 600; 
        letter-spacing: 0.3px; 
        margin: 15px 0 5px; 
        color: var(--bs-gray-900);
    }
    .rcpt-survey { 
        text-align: center; 
        font-size: 10px; 
        color: var(--bs-gray-600); 
        line-height: 1.5; 
    }

    /* ── Barcode ──────────────────────────────────────────────── */
    .rcpt-barcode { 
        text-align: center; 
        font-family: 'Libre Barcode 128', monospace; 
        font-size: 48px; 
        line-height: 1; 
        margin: 8px 0 2px; 
        color: var(--bs-gray-900); 
        overflow: hidden; 
        letter-spacing: 1px;
    }
    .rcpt-barcode-num { 
        text-align: center; 
        font-size: 10px; 
        letter-spacing: 2px; 
        color: var(--bs-gray-600); 
        margin-top: -5px; 
        font-weight: 500;
    }

    /* ── Print Styles ─────────────────────────────────────────── */
    @media print {
        body * { visibility: hidden !important; }
        #receipt-print-area, #receipt-print-area * { visibility: visible !important; }
        #receipt-print-area { 
            position: fixed !important; 
            top: 0 !important; 
            left: 0 !important; 
            width: 80mm !important; 
            background: #fff !important; 
            padding: 0 !important; 
            margin: 0 !important; 
            box-shadow: none !important;
        }
        #receipt-paper { 
            max-height: none !important; 
            overflow: visible !important; 
            border: none !important; 
            padding: 8px 12px !important; 
            background: white !important;
        }
        .modal-header, .modal-footer { 
            display: none !important; 
        }
    }
</style>


<div class="modal fade" id="receiptModal" tabindex="-1" aria-hidden="true"
     data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            {{-- Modal Header (Metronic Style) --}}
            <div class="modal-header bg-primary py-3 px-6">
                <div class="d-flex align-items-center">
                    <i class="ki-duotone ki-receipt-2 fs-2x text-white me-3">
                        <span class="path1"></span>
                        <span class="path2"></span>
                        <span class="path3"></span>
                        <span class="path4"></span>
                        <span class="path5"></span>
                    </i>
                    <div>
                        <h5 class="modal-title text-white fw-bold mb-0">{{ __('pagination.payment_receipt') }}</h5>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-light fw-bold px-4" id="rcpt-print-btn">
                        <i class="ki-duotone ki-printer fs-4 me-1">
                            <span class="path1"></span>
                            <span class="path2"></span>
                            <span class="path3"></span>
                        </i>
                        {{ __('pagination.print') }}
                    </button>
                    <button type="button" class="btn btn-sm btn-icon btn-light" onclick="window.location.reload();">
                        <i class="fas fa-times fs-4"></i>
                    </button>
                </div>
            </div>

            {{-- Receipt Content --}}
            <div id="receipt-print-area" style="background:#ffffff;">
                <div id="receipt-paper">

                    {{-- Store Header --}}
                    <div class="rcpt-store-name">{{ getUIOptions('app_name') }}</div>
                    <div class="rcpt-store-tagline">{{ __('pagination.receipt_tagline') }}</div>
                    <div class="rcpt-store-address">
                        {{ getUIOptions('app_email') }}<br>
                        {{ getUIOptions('app_contact') }}
                    </div>

                    <div class="rcpt-divider-solid"></div>

                    {{-- Order Meta --}}
                    <div class="rcpt-meta">
                        <div class="rcpt-meta-row">
                            <span>{{ __('pagination.order') }} #:</span>
                            <span id="rcpt-order-no">—</span>
                        </div>
                        <div class="rcpt-meta-row">
                            <span>{{ __('pagination.date') }}:</span>
                            <span id="rcpt-date">—</span>
                        </div>
                        <div class="rcpt-meta-row">
                            <span>{{ __('pagination.time') }}:</span>
                            <span id="rcpt-time">—</span>
                        </div>
                        <div class="rcpt-meta-row">
                            <span>{{ __('pagination.cashier') }}:</span>
                            <span id="rcpt-cashier">{{ auth()->user()->name ?? 'STAFF' }}</span>
                        </div>
                    </div>

                    {{-- Customer Banner --}}
                    <div class="rcpt-customer" id="rcpt-customer-banner">
                        <i class="ki-duotone ki-user fs-4 me-2"></i>
                        <span id="rcpt-customer-name">{{ __('pagination.customer') }}: GUEST</span>
                    </div>

                    {{-- Order Type --}}
                    <div style="text-align:center; margin:8px 0;">
                        <span class="rcpt-order-type" id="rcpt-order-type">{{ __('pagination.sale') }}</span>
                    </div>

                    <div class="rcpt-divider-dash"></div>

                    {{-- Items Table --}}
                    <table class="rcpt-items">
                        <thead>
                            <tr>
                                <th style="text-align:left;">{{ __('pagination.qty') }}</th>
                                <th style="text-align:left;">{{ __('pagination.item') }}</th>
                                <th style="text-align:right;">{{ __('pagination.price') }}</th>
                            </tr>
                        </thead>
                        <tbody id="rcpt-items-body"></tbody>
                    </table>

                    <div class="rcpt-divider-dash"></div>

                    {{-- Totals --}}
                    <table class="rcpt-totals">
                        <tbody>
                            <tr>
                                <td class="rcpt-total-label">{{ __('pagination.subtotal') }}</td>
                                <td class="rcpt-total-value" id="rcpt-subtotal">—</td>
                            </tr>
                            <tr class="d-none" id="rcpt-discount-row">
                                <td class="rcpt-total-label">{{ __('pagination.discount') }}</td>
                                <td class="rcpt-total-value" id="rcpt-discount" style="color: var(--bs-danger);">—</td>
                            </tr>
                            <tr class="d-none" id="rcpt-tax-row">
                                <td class="rcpt-total-label">{{ __('pagination.tax') }}</td>
                                <td class="rcpt-total-value" id="rcpt-tax">—</td>
                            </tr>
                            <tr class="rcpt-grand-row">
                                <td class="rcpt-total-label">{{ __('pagination.total') }}</td>
                                <td class="rcpt-grand-value" id="rcpt-grand-total">—</td>
                            </tr>
                        </tbody>
                    </table>

                    <div class="rcpt-divider-solid"></div>

                    {{-- Payment Breakdown --}}
                    <div style="font-size:12px; font-weight:600; color: var(--bs-gray-800); margin-bottom:6px;">
                        {{ __('pagination.payment_methods') }}
                    </div>
                    <table class="rcpt-payments">
                        <tbody id="rcpt-payments-body"></tbody>
                    </table>

                    {{-- Change Due --}}
                    <div class="rcpt-change-box d-none" id="rcpt-change-box">
                        <div class="rcpt-change-label">{{ __('pagination.change_due') }}</div>
                        <div class="rcpt-change-value" id="rcpt-change-value">0.00</div>
                    </div>

                    <div class="rcpt-divider-star">• • • • • • • • • • • • • • • •</div>

                    {{-- Item Count --}}
                    <div class="rcpt-meta">
                        <div class="rcpt-meta-row">
                            <span>{{ __('pagination.total_items') }}:</span>
                            <span id="rcpt-item-count">0</span>
                        </div>
                    </div>

                    {{-- Thank You Message --}}
                    <div class="rcpt-thankyou">{{ __('pagination.thank_you') }}</div>
                    <div class="rcpt-survey">
                        {{ __('pagination.receipt_footer_message') }}<br>
                        <span style="color: var(--bs-primary);">{{ __('stardena.com') }}</span>
                    </div>

                    {{-- Barcode --}}
                    <div class="rcpt-barcode" id="rcpt-barcode">000000000000</div>
                    <div class="rcpt-barcode-num" id="rcpt-barcode-num">000000000000</div>
                    <div style="height:10px;"></div>

                </div>{{-- /receipt-paper --}}
            </div>{{-- /receipt-print-area --}}

        </div>
    </div>
</div>

<script>
    (function() {
        // Print button handler
        document.getElementById('rcpt-print-btn')?.addEventListener('click', function() {
            window.print();
        });

        // Initialize tooltips
        if (typeof tippy !== 'undefined') {
            tippy('[data-bs-toggle="tooltip"]');
        }

        // Receipt generation function
        window.generateMultiPaymentReceipt = function(order) {
            // console.log('Generating receipt:', order);

            const SYM = '{{ currency_symbol() }}';
            
            function fmt(n) {
                return SYM + parseFloat(n || 0).toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            }

            function pad(n) { return String(n).padStart(2, '0'); }

            // Get current date/time if not provided
            const now = new Date();
            const date = order.date || `${pad(now.getDate())}/${pad(now.getMonth()+1)}/${now.getFullYear()}`;
            const time = order.time || `${pad(now.getHours())}:${pad(now.getMinutes())}:${pad(now.getSeconds())}`;

            // Set meta
            document.getElementById('rcpt-order-no').textContent = order.order_number || order.ref || 'N/A';
            document.getElementById('rcpt-date').textContent = date;
            document.getElementById('rcpt-time').textContent = time;
            document.getElementById('rcpt-cashier').textContent = order.cashier || '{{ auth()->user()->name ?? "STAFF" }}';
            
            // Customer name
            const customerName = order.customer_name || order.customer?.name || 'GUEST';
            document.getElementById('rcpt-customer-name').textContent = '{{ __("pagination.customer") }}: ' + customerName.toUpperCase();
            
            // Order type
            document.getElementById('rcpt-order-type').textContent = (order.order_type || order.type || 'SALE').toUpperCase();

            // Items
            const items = order.items || [];
            const itemsBody = document.getElementById('rcpt-items-body');
            let itemCount = 0;
            
            if (items.length) {
                itemsBody.innerHTML = items.map(item => {
                    const qty = parseInt(item.quantity || item.qty || 1);
                    const price = parseFloat(item.price || item.unit_price || 0);
                    const total = parseFloat(item.total || (qty * price));
                    itemCount += qty;
                    
                    return `
                        <tr>
                            <td class="rcpt-item-qty">${qty}</td>
                            <td class="rcpt-item-name">${item.name || item.item_name || 'Item'}</td>
                            <td class="rcpt-item-price">${fmt(total)}</td>
                        </tr>
                        ${item.note ? `<tr><td></td><td colspan="2" class="rcpt-item-sub">↳ ${item.note}</td></tr>` : ''}
                    `;
                }).join('');
            } else {
                itemsBody.innerHTML = '<tr><td colspan="3" style="color: var(--bs-gray-500); text-align:center;">No items</td></tr>';
            }
            document.getElementById('rcpt-item-count').textContent = itemCount;

            // Totals
            const subtotal = parseFloat(order.subtotal || order.total || 0);
            const discount = parseFloat(order.discount || 0);
            const tax = parseFloat(order.tax || 0);
            const total = parseFloat(order.total || 0);

            document.getElementById('rcpt-subtotal').textContent = fmt(subtotal);
            
            if (discount > 0) {
                document.getElementById('rcpt-discount-row').classList.remove('d-none');
                document.getElementById('rcpt-discount').textContent = '-' + fmt(discount);
            } else {
                document.getElementById('rcpt-discount-row').classList.add('d-none');
            }
            
            if (tax > 0) {
                document.getElementById('rcpt-tax-row').classList.remove('d-none');
                document.getElementById('rcpt-tax').textContent = fmt(tax);
            } else {
                document.getElementById('rcpt-tax-row').classList.add('d-none');
            }
            
            document.getElementById('rcpt-grand-total').textContent = fmt(total);

            // Payments
            const payments = order.payments || [];
            const paymentsBody = document.getElementById('rcpt-payments-body');
            let totalChange = 0;
            
            if (payments.length) {
                paymentsBody.innerHTML = payments.map(p => {
                    const change = parseFloat(p.change || 0);
                    totalChange += change;
                    
                    let methodLabel = p.method_name || p.type || 'Payment';
                    methodLabel = methodLabel.charAt(0).toUpperCase() + methodLabel.slice(1).replace('_', ' ');
                    
                    return `
                        <tr>
                            <td class="rcpt-pay-label">${methodLabel}</td>
                            <td class="rcpt-pay-value">${fmt(p.amount || p.tendered || 0)}</td>
                        </tr>
                        ${p.transaction_reference ? `
                        <tr>
                            <td colspan="2" style="font-size:10px; color: var(--bs-gray-600); padding-left:12px;">
                                Ref: ${p.transaction_reference}
                            </td>
                        </tr>` : ''}
                    `;
                }).join('');
            } else {
                paymentsBody.innerHTML = `
                    <tr>
                        <td class="rcpt-pay-label">{{ __('pagination.paid') }}</td>
                        <td class="rcpt-pay-value">${fmt(total)}</td>
                    </tr>`;
            }

            // Change due
            if (totalChange > 0.005) {
                document.getElementById('rcpt-change-box').classList.remove('d-none');
                document.getElementById('rcpt-change-value').textContent = fmt(totalChange);
            } else {
                document.getElementById('rcpt-change-box').classList.add('d-none');
            }

            // Barcode (order number)
            const barcodeVal = String(order.order_number || order.ref || order.order_ref || '0').replace(/\D/g, '').padEnd(12, '0').slice(0, 12);
            document.getElementById('rcpt-barcode').textContent = barcodeVal;
            document.getElementById('rcpt-barcode-num').textContent = barcodeVal;

            // Show modal
            bootstrap.Modal.getOrCreateInstance(document.getElementById('receiptModal')).show();
        };
    })();
</script>