{{--
    Bulk-create inventory placeholder rows for many variant × department
    combinations in one submission. Every row is created with
    quantity_on_hand = 0 — this is for seeding the grid, not receiving stock.

    Assumes $variants, $departments and $locations are available in scope
    (passed from InventoryItemController@index alongside the single-item
    "create" modal it sits next to).
--}}
<div class="modal fade" id="kt_modal_batch_add_inventory" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered mw-1000px">
        <div class="modal-content">
            <div class="modal-header">
                <h2 class="fw-bold">{{ __('pagination.bulk_add_inventory') }}</h2>
                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                    <i class="ki-duotone ki-cross fs-1">
                        <span class="path1"></span>
                        <span class="path2"></span>
                    </i>
                </div>
            </div>

            <div class="modal-body px-5 my-5">
                <div class="alert alert-light-info d-flex align-items-center mb-6">
                    <i class="bi bi-info-circle fs-2 me-3"></i>
                    <div>
                        {{ __('pagination.bulk_add_inventory_hint') }}
                    </div>
                </div>

                <form id="kt_modal_batch_add_inventory_form" data-action="{{ route('items.batchStore') }}">
                    @csrf
                    <div class="row g-6">

                        {{-- ── VARIANTS ─────────────────────────────────────────── --}}
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="fs-6 fw-semibold">{{ __('pagination.product_variants') }}</label>
                                <div class="form-check form-check-sm form-check-custom">
                                    <input class="form-check-input" type="checkbox" id="select_all_variants">
                                    <label class="form-check-label fs-8 text-muted" for="select_all_variants">
                                        {{ __('auth._select_all') }}
                                    </label>
                                </div>
                            </div>
                            <input type="text"
                                id="batch_variant_search"
                                class="form-control form-control-solid mb-3"
                                placeholder="{{ __('auth._search') }} SKU / {{ __('auth._name') }}..."
                                autocomplete="off">

                            <div class="border rounded p-3 batch-scroll-list" style="max-height: 360px; overflow-y: auto;">
                                @forelse ($variants as $variant)
                                    <div class="form-check form-check-sm form-check-custom mb-2 variant-row"
                                         data-search="{{ strtolower(($variant->sku ?? '') . ' ' . $variant->name) }}">
                                        <input class="form-check-input variant-checkbox"
                                               type="checkbox"
                                               value="{{ $variant->id }}"
                                               id="var_{{ $variant->id }}">
                                        <label class="form-check-label" for="var_{{ $variant->id }}">
                                            <span class="badge badge-light-secondary fs-8 me-1">{{ $variant->sku }}</span>
                                            {{ $variant->name }}
                                        </label>
                                    </div>
                                @empty
                                    <div class="text-muted fs-7">{{ __('pagination.no_items_found') }}</div>
                                @endforelse
                            </div>
                        </div>

                        {{-- ── DEPARTMENTS (grouped by location) ───────────────── --}}
                        <div class="col-md-6">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="fs-6 fw-semibold">{{ __('auth._department') }}</label>
                            </div>
                            <input type="text"
                                id="batch_department_search"
                                class="form-control form-control-solid mb-3"
                                placeholder="{{ __('auth._search') }} {{ __('auth._department') }} / {{ __('pagination._location') }}..."
                                autocomplete="off">

                            <div class="border rounded p-3 batch-scroll-list" style="max-height: 360px; overflow-y: auto;">
                                @php $departmentsByLocation = $departments->groupBy('location_id'); @endphp

                                @forelse ($locations as $location)
                                    @php $deptsForLocation = $departmentsByLocation->get($location->id, collect()); @endphp

                                    @if ($deptsForLocation->count())
                                        <div class="d-flex align-items-center justify-content-between mt-3 mb-1">
                                            <span class="fw-bold fs-7 text-gray-700">{{ $location->name }}</span>
                                            <div class="form-check form-check-sm form-check-custom">
                                                <input class="form-check-input select-location"
                                                       type="checkbox"
                                                       data-location="{{ $location->id }}"
                                                       id="loc_all_{{ $location->id }}">
                                                <label class="form-check-label fs-8 text-muted" for="loc_all_{{ $location->id }}">
                                                    {{ __('auth._select_all') }}
                                                </label>
                                            </div>
                                        </div>

                                        @foreach ($deptsForLocation as $dept)
                                            <div class="form-check form-check-sm form-check-custom mb-2 dept-row"
                                                 data-location="{{ $location->id }}"
                                                 data-search="{{ strtolower($dept->name . ' ' . $location->name) }}">
                                                <input class="form-check-input dept-checkbox"
                                                       type="checkbox"
                                                       value="{{ $dept->id }}"
                                                       id="dept_{{ $dept->id }}">
                                                <label class="form-check-label" for="dept_{{ $dept->id }}">
                                                    {{ $dept->name }}
                                                </label>
                                            </div>
                                        @endforeach
                                    @endif
                                @empty
                                    <div class="text-muted fs-7">{{ __('pagination.no_items_found') }}</div>
                                @endforelse
                            </div>
                        </div>
                    </div>

                    <div class="separator my-6"></div>

                    <div class="d-flex align-items-center justify-content-between">
                        <span id="batch_summary" class="fs-6 fw-semibold text-gray-700">
                            0 {{ __('pagination.product_variants') }} × 0 {{ __('auth._department') }} = 0 {{ __('pagination.records') }}
                        </span>

                        <div>
                            <button type="button" id="batchDiscardButton" class="btn btn-light me-3" data-bs-dismiss="modal">
                                {{ __('auth._discard') }}
                            </button>
                            <button type="button" id="batchSubmitButton" class="btn btn-primary" disabled>
                                <span class="indicator-label">{{ __('auth.submit') }}</span>
                                <span class="indicator-progress" style="display: none;">
                                    {{ __('auth.please_wait') }}
                                    <span class="spinner-border spinner-border-sm align-middle ms-2"></span>
                                </span>
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const modal = document.getElementById('kt_modal_batch_add_inventory');
    if (!modal) return;

    const variantSearch     = modal.querySelector('#batch_variant_search');
    const deptSearch        = modal.querySelector('#batch_department_search');
    const selectAllVariants = modal.querySelector('#select_all_variants');
    const summary           = modal.querySelector('#batch_summary');
    const submitBtn         = modal.querySelector('#batchSubmitButton');
    const form              = modal.querySelector('#kt_modal_batch_add_inventory_form');

    /* ── Summary + button enable state ─────────────────────── */
    function updateSummary() {
        const vCount = modal.querySelectorAll('.variant-checkbox:checked').length;
        const dCount = modal.querySelectorAll('.dept-checkbox:checked').length;
        summary.textContent =
            `${vCount} {{ __('pagination.product_variants') }} × ${dCount} {{ __('auth._department') }} = ${vCount * dCount} {{ __('pagination.records') }}`;
        submitBtn.disabled = (vCount === 0 || dCount === 0);
    }

    /* ── Change handler: checkboxes + per-location select-all ─ */
    modal.addEventListener('change', function (e) {
        if (e.target.classList.contains('variant-checkbox') || e.target.classList.contains('dept-checkbox')) {
            updateSummary();
        }

        if (e.target.classList.contains('select-location')) {
            const locId = e.target.dataset.location;
            modal.querySelectorAll(`.dept-row[data-location="${locId}"]`).forEach(row => {
                if (row.style.display !== 'none') {
                    row.querySelector('.dept-checkbox').checked = e.target.checked;
                }
            });
            updateSummary();
        }
    });

    /* ── Select-all variants ────────────────────────────────── */
    selectAllVariants.addEventListener('change', function () {
        modal.querySelectorAll('.variant-row').forEach(row => {
            if (row.style.display !== 'none') {
                row.querySelector('.variant-checkbox').checked = selectAllVariants.checked;
            }
        });
        updateSummary();
    });

    /* ── Search filters ─────────────────────────────────────── */
    variantSearch.addEventListener('input', function () {
        const term = this.value.toLowerCase();
        modal.querySelectorAll('.variant-row').forEach(row => {
            row.style.display = row.dataset.search.includes(term) ? '' : 'none';
        });
    });

    deptSearch.addEventListener('input', function () {
        const term = this.value.toLowerCase();
        modal.querySelectorAll('.dept-row').forEach(row => {
            row.style.display = row.dataset.search.includes(term) ? '' : 'none';
        });
    });

    /* ── Submit ─────────────────────────────────────────────── */
    submitBtn.addEventListener('click', async function () {
        const variantIds    = Array.from(modal.querySelectorAll('.variant-checkbox:checked')).map(cb => cb.value);
        const departmentIds = Array.from(modal.querySelectorAll('.dept-checkbox:checked')).map(cb => cb.value);

        if (!variantIds.length || !departmentIds.length) return;

        setBatchBtnLoading(submitBtn, true);

        try {
            const res = await fetch(form.dataset.action, {
                method: 'POST',
                headers: {
                    'Content-Type':  'application/json',
                    'Accept':        'application/json',
                    'X-CSRF-TOKEN':  document.querySelector('meta[name="csrf-token"]')?.content || '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ variant_ids: variantIds, department_ids: departmentIds }),
            });

            const data = await res.json().catch(() => ({}));

            if (!res.ok || data.success === false) {
                throw new Error(data.message || ('HTTP ' + res.status));
            }

            /* Success */
            bootstrap.Modal.getInstance(modal)?.hide();
            window.toastr?.success?.(data.message) ?? alert(data.message);

            // Reset checks so re-opening is clean
            modal.querySelectorAll('.variant-checkbox, .dept-checkbox, .select-location, #select_all_variants')
                 .forEach(cb => { cb.checked = false; });
            updateSummary();

            // Refresh the list in place
            window.InventoryList?.reload?.();

        } catch (err) {
            console.error('[batch-create] failed:', err);
            if (typeof toastr !== 'undefined') toastr.error(err.message);
            else alert(err.message);
        } finally {
            setBatchBtnLoading(submitBtn, false);
            // Re-evaluate disabled state — if user unchecked everything
            // while the request was in flight, keep the button locked.
            updateSummary();
        }
    });

    /* ── Button loading (matches create modal pattern) ─────── */
    function setBatchBtnLoading(btn, busy) {
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
            if (label)    label.style.visibility = 'hidden';
            if (progress) {
                progress.classList.remove('d-none');
                progress.style.display = 'inline-flex';
            }
            // Lock min-width so the button doesn't shrink when the label hides
            btn.style.minWidth = btn.offsetWidth + 'px';
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
});
</script>

<style>
    /* Batch submit button — expand + centre the spinner */
#batchSubmitButton {
    position: relative;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 140px;                 /* floor so it never shrinks to icon-width */
    transition: min-width 0.25s ease, padding 0.25s ease;
    white-space: nowrap;
    overflow: hidden;                 /* clip anything that tries to escape */
}

#batchSubmitButton .indicator-progress {
    position: absolute;
    inset: 0;                         /* fill the button — no translate tricks */
    display: none;                    /* toggled by JS */
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    white-space: nowrap;
    pointer-events: none;
}

#batchSubmitButton.is-loading .indicator-label {
    visibility: hidden;
}

#batchSubmitButton.is-loading .indicator-progress {
    display: inline-flex;
}

#batchSubmitButton:disabled {
    opacity: 0.85;
    cursor: progress;
}
</style>