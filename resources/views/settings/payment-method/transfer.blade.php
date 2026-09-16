<div class="modal fade" id="transferFundsModal{{ $paymentMethod->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">
                    <i class="ki-duotone ki-arrows-circle fs-2 me-2 text-primary">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                    {{ __('payments.transfer_funds') }} — {{ $paymentMethod->name }}
                </h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1"><span class="path1"></span><span class="path2"></span></i>
                </div>
            </div>

            <div class="modal-body px-5 my-7">
                <form id="transferFundsForm{{ $paymentMethod->id }}" class="form">
                    @csrf

                    {{-- Source is locked to THIS row's payment method --}}
                    <input type="hidden" name="from_payment_method_id" value="{{ $paymentMethod->id }}">

                    <div class="row g-9 mb-6">
                        <!-- ── FROM ACCOUNT (read-only, this row) ──────────── -->
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2 d-flex align-items-center">
                                {{ __('payments.from_account') }}
                                <span class="badge badge-light-primary ms-2 fs-8">{{ __('payments.locked') }}</span>
                            </label>

                            <div class="d-flex align-items-center bg-light-primary border border-primary border-dashed rounded-3 p-3">
                                <div class="symbol symbol-40px me-3">
                                    <span class="symbol-label bg-primary">
                                        <i class="ki-duotone ki-wallet fs-2x text-white">
                                            <span class="path1"></span><span class="path2"></span>
                                        </i>
                                    </span>
                                </div>
                                <div class="d-flex flex-column">
                                    <span class="fw-bold text-gray-800">{{ $paymentMethod->name }}</span>
                                    <span class="fs-7 text-gray-600">
                                    <span class="fs-7 text-gray-600">
                                        {{ __('payments.available') }}:
                                        <strong>
                                            {{ number_format($paymentMethod->current_balance, 2) }}
                                            {{ $paymentMethod->currency?->code ?? currency_code() }}
                                        </strong>
                                        <span class="text-muted ms-2">({{ $paymentMethod->getTypeLabel() }})</span>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- ── TO ACCOUNT (excluding this row) ──────────────── -->
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.to_account') }}</label>
                            <select class="form-select form-select-solid"
                                    name="to_payment_method_id"
                                    data-control="select2"
                                    data-dropdown-parent="#transferFundsModal{{ $paymentMethod->id }}"
                                    data-placeholder="{{ __('payments.select_destination_account') }}">
                                <option value=""></option>
                                @foreach($all_payment_methods as $pm)
                                    @if($pm->is_active && $pm->id !== $paymentMethod->id)
                                        <option value="{{ $pm->id }}">
                                            {{ $pm->name }}
                                            — {{ number_format($pm->current_balance, 2) }}
                                            {{ $pm->currency?->code ?? currency_code() }}
                                            @if($pm->provider) · {{ $pm->provider }} @endif
                                        </option>
                                    @endif
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">
                                {{ __('payments.destination_must_differ') }}
                            </small>
                        </div>
                    </div>

                    <div class="row g-9 mb-6">
                        <div class="col-md-6">
                            <label class="required fs-6 fw-semibold mb-2">{{ __('payments.amount') }}</label>
                            <div class="input-group">
                                <span class="input-group-text">{{ currency_symbol() }}</span>
                                <input type="number" step="0.01" min="0.01"
                                    class="form-control form-control-solid transfer-amount"
                                    name="amount"
                                    id="transferAmount{{ $paymentMethod->id }}"
                                    data-source-id="{{ $paymentMethod->id }}"
                                    data-source-balance="{{ $paymentMethod->current_balance }}"
                                    data-allow-negative="{{ $paymentMethod->allow_negative_balance ? 1 : 0 }}"
                                    data-currency="{{ $paymentMethod->currency?->code ?? currency_code() }}" />
                            </div>
                            <small class="text-muted d-block mt-1 transfer-hint">
                                {{ __('payments.available') }}:
                                <strong>
                                    {{ number_format($paymentMethod->current_balance, 2) }}
                                    {{ $paymentMethod->currency?->code ?? currency_code() }}
                                </strong>
                            </small>
                        </div>

                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">{{ __('payments.description') }}</label>
                            <input type="text" class="form-control form-control-solid"
                                   name="description"
                                   placeholder="{{ __('payments.optional_note') }}" />
                        </div>
                    </div>

                    <!-- Preview panel (per-modal) -->
                    <div class="transfer-preview d-none">
                        <div class="separator separator-dashed my-6"></div>
                        <div class="row g-5">
                            <div class="col-md-6">
                                <div class="card card-flush bg-light-danger h-100">
                                    <div class="card-body py-5">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="ki-duotone ki-arrow-up fs-2 text-danger me-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <span class="fw-bold text-gray-800">{{ __('payments.outgoing') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fs-7">{{ __('payments.account') }}</span>
                                            <span class="fw-bold preview-from-name">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fs-7">{{ __('payments.balance_before') }}</span>
                                            <span class="preview-from-before">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fs-7">{{ __('payments.amount') }}</span>
                                            <span class="text-danger fw-bold preview-from-amount">-</span>
                                        </div>
                                        <div class="separator separator-dashed my-2"></div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted fs-7">{{ __('payments.balance_after') }}</span>
                                            <span class="fw-bold preview-from-after">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <div class="card card-flush bg-light-success h-100">
                                    <div class="card-body py-5">
                                        <div class="d-flex align-items-center mb-3">
                                            <i class="ki-duotone ki-arrow-down fs-2 text-success me-2">
                                                <span class="path1"></span><span class="path2"></span>
                                            </i>
                                            <span class="fw-bold text-gray-800">{{ __('payments.incoming') }}</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fs-7">{{ __('payments.account') }}</span>
                                            <span class="fw-bold preview-to-name">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fs-7">{{ __('payments.balance_before') }}</span>
                                            <span class="preview-to-before">-</span>
                                        </div>
                                        <div class="d-flex justify-content-between mb-1">
                                            <span class="text-muted fs-7">{{ __('payments.amount') }}</span>
                                            <span class="text-success fw-bold preview-to-amount">-</span>
                                        </div>
                                        <div class="separator separator-dashed my-2"></div>
                                        <div class="d-flex justify-content-between">
                                            <span class="text-muted fs-7">{{ __('payments.balance_after') }}</span>
                                            <span class="fw-bold preview-to-after">-</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="alert bg-light-primary border border-primary border-dashed d-flex align-items-center mt-6 p-4">
                            <i class="ki-duotone ki-information-5 fs-2x text-primary me-3">
                                <span class="path1"></span><span class="path2"></span><span class="path3"></span>
                            </i>
                            <div class="d-flex flex-column">
                                <span class="fw-bold">{{ __('payments.reference') }}:</span>
                                <span class="fs-7 preview-reference">-</span>
                            </div>
                        </div>
                    </div>
                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                    {{ __('auth._discard') }}
                </button>
                <button type="button" class="btn btn-light-primary me-2 transfer-preview-btn">
                    <i class="ki-duotone ki-eye fs-2 me-1"></i>
                    {{ __('payments.preview') }}
                </button>
                <button type="button" class="btn btn-primary transfer-execute-btn" disabled>
                    <span class="indicator-label">
                        <i class="ki-duotone ki-check-circle fs-2 me-1"></i>
                        {{ __('payments.confirm_transfer') }}
                    </span>
                    <span class="indicator-progress">
                        {{ __('auth.please_wait') }}
                        <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>




<script>
document.addEventListener('DOMContentLoaded', function () {

    document.querySelectorAll('.modal[id^="transferFundsModal"]').forEach(function (modal) {
        const form         = modal.querySelector('form');
        const fromInput    = form.querySelector('input[name="from_payment_method_id"]');
        const toSelect     = form.querySelector('select[name="to_payment_method_id"]');
        const amountInput  = form.querySelector('.transfer-amount');
        const descInput    = form.querySelector('input[name="description"]');
        const hintEl       = form.querySelector('.transfer-hint');
        const previewBtn   = modal.querySelector('.transfer-preview-btn');
        const executeBtn   = modal.querySelector('.transfer-execute-btn');
        const previewPanel = modal.querySelector('.transfer-preview');

        // ✅ Single source of truth: current_balance
        const sourceBalance = parseFloat(amountInput.dataset.sourceBalance || 0);
        const allowNegative = amountInput.dataset.allowNegative === '1';
        const currency      = amountInput.dataset.currency || '';

        // ── Reset on open ─────────────────────────────────────────
        modal.addEventListener('show.bs.modal', function () {
            form.reset();
            $(toSelect).val(null).trigger('change');
            previewPanel.classList.add('d-none');
            executeBtn.disabled = true;
            amountInput.classList.remove('is-invalid');
            amountInput.setAttribute('max', sourceBalance);

            hintEl.innerHTML = `
                {{ __('payments.available') }}: <strong>${sourceBalance.toFixed(2)} ${currency}</strong>
                ${allowNegative ? `<span class="text-warning ms-2">({{ __('payments.negative_balance_allowed') }})</span>` : ''}
            `;
            hintEl.className = 'text-muted d-block mt-1 transfer-hint';
        });

        // ── Destination change ────────────────────────────────────
        $(toSelect).on('change', function () {
            previewPanel.classList.add('d-none');
            executeBtn.disabled = true;
        });

        // ── Amount live validation (uses current_balance only) ────
        amountInput.addEventListener('input', function () {
            previewPanel.classList.add('d-none');
            executeBtn.disabled = true;

            const amt = parseFloat(this.value || 0);

            if (!allowNegative && amt > sourceBalance) {
                this.classList.add('is-invalid');
                hintEl.innerHTML = `<span class="text-danger">{{ __('payments.insufficient_balance') }}</span>`;
                hintEl.className = 'd-block mt-1 transfer-hint';
            } else if (allowNegative && amt > sourceBalance) {
                // Negative balance is allowed: show a warning but let them proceed
                this.classList.remove('is-invalid');
                hintEl.innerHTML = `
                    <span class="text-warning">
                        {{ __('payments.will_exceed_balance') }} ({{ __('payments.negative_balance_allowed') }})
                    </span>
                `;
                hintEl.className = 'd-block mt-1 transfer-hint';
            } else {
                this.classList.remove('is-invalid');
                hintEl.innerHTML = `{{ __('payments.available') }}: <strong>${sourceBalance.toFixed(2)} ${currency}</strong>`;
                hintEl.className = 'text-muted d-block mt-1 transfer-hint';
            }
        });

        // ── PREVIEW ───────────────────────────────────────────────
        previewBtn.addEventListener('click', function () {
            const payload = {
                from_payment_method_id: fromInput.value,
                to_payment_method_id:   toSelect.value,
                amount:                 amountInput.value,
                description:            descInput.value,
                _token:                 '{{ csrf_token() }}',
            };

            if (!payload.from_payment_method_id || !payload.to_payment_method_id || !payload.amount) {
                toastr.warning('{{ __('payments.fill_all_required_fields') }}');
                return;
            }

            LiveBlade.toggleButtonLoading(previewBtn, true);

            fetch('{{ route('paymentmethod.transfer.preview') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept':       'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                },
                body: JSON.stringify(payload),
            })
            .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
            .then(({ ok, data }) => {
                LiveBlade.toggleButtonLoading(previewBtn, false);

                if (!ok || !data.success) {
                    toastr.error(data.message || '{{ __('payments.validation_failed') }}');
                    return;
                }

                const p   = data.preview;
                const cur = '{{ currency_symbol() }}';

                modal.querySelector('.preview-from-name').textContent   = p.from.name;
                modal.querySelector('.preview-from-before').textContent = `${p.from.balance_before.toFixed(2)} ${cur}`;
                modal.querySelector('.preview-from-amount').textContent = `- ${p.amount.toFixed(2)} ${cur}`;
                modal.querySelector('.preview-from-after').textContent  = `${p.from.balance_after.toFixed(2)} ${cur}`;

                modal.querySelector('.preview-to-name').textContent     = p.to.name;
                modal.querySelector('.preview-to-before').textContent   = `${p.to.balance_before.toFixed(2)} ${cur}`;
                modal.querySelector('.preview-to-amount').textContent   = `+ ${p.amount.toFixed(2)} ${cur}`;
                modal.querySelector('.preview-to-after').textContent    = `${p.to.balance_after.toFixed(2)} ${cur}`;

                modal.querySelector('.preview-reference').textContent = p.reference;

                previewPanel.classList.remove('d-none');
                executeBtn.disabled = false;
            })
            .catch(err => {
                LiveBlade.toggleButtonLoading(previewBtn, false);
                console.error(err);
                toastr.error('{{ __('payments.network_error') }}');
            });
        });

        // ── EXECUTE ───────────────────────────────────────────────
        executeBtn.addEventListener('click', function () {
            Swal.fire({
                title: '{{ __('payments.confirm_transfer_title') }}',
                html:  '{{ __('payments.confirm_transfer_message') }}',
                icon:  'warning',
                showCancelButton: true,
                confirmButtonText: '{{ __('payments.yes_transfer') }}',
                cancelButtonText:  '{{ __('auth._discard') }}',
                confirmButtonColor: '#009ef7',
            }).then((result) => {
                if (!result.isConfirmed) return;

                const payload = {
                    from_payment_method_id: fromInput.value,
                    to_payment_method_id:   toSelect.value,
                    amount:                 amountInput.value,
                    description:            descInput.value,
                    _token:                 '{{ csrf_token() }}',
                };

                LiveBlade.toggleButtonLoading(executeBtn, true);

                fetch('{{ route('paymentmethod.transfer') }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    },
                    body: JSON.stringify(payload),
                })
                .then(r => r.json().then(d => ({ ok: r.ok, data: d })))
                .then(({ ok, data }) => {
                    LiveBlade.toggleButtonLoading(executeBtn, false);

                    if (!ok || !data.success) {
                        toastr.error(data.message || '{{ __('payments.transfer_failed') }}');
                        return;
                    }

                    toastr.success(data.message || '{{ __('payments.transfer_completed') }}');
                    bootstrap.Modal.getInstance(modal).hide();

                    if (typeof window.reloadPaymentMethods === 'function') {
                        window.reloadPaymentMethods();
                    } else {
                        setTimeout(() => window.location.reload(), 600);
                    }
                })
                .catch(err => {
                    LiveBlade.toggleButtonLoading(executeBtn, false);
                    console.error(err);
                    toastr.error('{{ __('payments.network_error') }}');
                });
            });
        });
    });
});
</script>