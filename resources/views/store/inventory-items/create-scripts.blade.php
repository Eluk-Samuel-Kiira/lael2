<script>
    /**
 * Submit any create/update form via fetch — vanilla replacement
 * for the old LiveBlade helper.
 *
 * @param {string} formId
 * @param {string} submitButtonId
 * @param {string} url
 * @param {string} method   'POST' | 'PUT' | 'PATCH' | 'DELETE'
 * @param {string} discardButtonId  button to click on success (usually closes modal)
 */
async function submitItemForm(formId, submitButtonId, url, method = 'POST', discardButtonId = 'discardButton') {
    const form         = document.getElementById(formId);
    const submitButton = document.getElementById(submitButtonId);

    if (!form || !submitButton) {
        console.error('[submitItemForm] form or button not found:', formId, submitButtonId);
        return;
    }

    const formData = new FormData(form);

    // Laravel's method spoofing — only for PUT/PATCH/DELETE.
    // POST and GET must go through as-is.
    if (method && method.toUpperCase() !== 'POST') {
        formData.set('_method', method.toUpperCase());
    }

    const csrf = document.querySelector('meta[name="csrf-token"]')?.content
              || form.querySelector('input[name="_token"]')?.value
              || '';

    setButtonLoading(submitButton, true);
    clearFieldErrors(form);

    try {
        const res = await fetch(url, {
            method: 'POST', // always POST on the wire; _method spoofs it
            headers: {
                'X-CSRF-TOKEN': csrf,
                'Accept':       'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: formData,
        });

        const json = await res.json().catch(() => ({}));

        // Laravel validation errors come back as 422 with `errors` map
        if (res.status === 422 && json.errors) {
            renderFieldErrors(form, json.errors);
            window.toastr?.error?.(json.message || 'Please fix the highlighted fields.');
            return;
        }

        if (!res.ok || json.success === false) {
            throw new Error(json.message || ('HTTP ' + res.status));
        }

        // Success
        window.toastr?.success?.(json.message || '{{ __("auth._created") }}');

        // Close the modal (or whatever discard does)
        if (discardButtonId) {
            const discard = document.getElementById(discardButtonId);
            if (discard) discard.click();
        }

        // Refresh the table if the list is on the page
        if (json.reload && window.InventoryList?.reload) {
            window.InventoryList.reload();
        }

        // Reset the form so re-opening is clean
        try { form.reset(); } catch (e) {}

        // Clear any stale dropdown state
        const variantSearch = form.querySelector('#variant_search');
        const variantId     = form.querySelector('#variant_id');
        if (variantSearch) variantSearch.value = '';
        if (variantId)     variantId.value = '';

        // If the backend asks for a redirect, honour it
        if (json.redirect) {
            // Most flows don't need this because we reload in place,
            // but keep it as an escape hatch for other forms.
            // window.location.href = json.redirect;
        }

    } catch (err) {
        console.error('[submitItemForm] submit failed:', err);
        window.toastr?.error?.(err.message || '{{ __("auth.create_failed") }}');
    } finally {
        setButtonLoading(submitButton, false);
    }
}

/* ── Button loading (same visual as LiveBlade's helper) ─────── */
function setButtonLoading(btn, busy) {
    if (!btn) return;
    if (getComputedStyle(btn).position === 'static') btn.style.position = 'relative';

    const label    = btn.querySelector('.indicator-label');
    const progress = btn.querySelector('.indicator-progress');

    // Progress overlay positioning (unchanged)
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
        // ── Measure what the button needs while busy ──
        // Read the label text (it's visible before we hide it)
        const labelText = label ? label.textContent.trim() : '';

        // Hide the label so we can measure the progress content cleanly
        if (label) label.style.visibility = 'hidden';

        // Show the progress so it has real width
        if (progress) {
            progress.classList.remove('d-none');
            progress.style.display = 'inline-flex';
            progress.style.position = 'static';
            progress.style.transform = 'none';
        }

        // Snapshot both widths, then lock min-width to the larger one
        const labelWidth    = btn.scrollWidth; // currently shows progress
        // Re-measure what the label alone would be by temporarily showing it
        if (label && progress) {
            progress.style.display = 'none';
            label.style.visibility = 'visible';
            const labelOnlyWidth = btn.scrollWidth;
            label.style.visibility = 'hidden';
            progress.style.display = 'inline-flex';

            btn.style.minWidth = Math.max(labelWidth, labelOnlyWidth, btn.offsetWidth) + 'px';
        } else {
            btn.style.minWidth = btn.offsetWidth + 'px';
        }

        // Restore the overlay positioning for the progress text
        if (progress) {
            progress.style.position = 'absolute';
            progress.style.top = '50%';
            progress.style.left = '50%';
            progress.style.transform = 'translate(-50%, -50%)';
            progress.style.display = 'inline-flex';
        }

        btn.disabled = true;
        return;
    }

    // ── Restoring (busy = false) ──
    btn.disabled = false;
    btn.style.minWidth = '';
    if (label)    label.style.visibility = '';
    if (progress) {
        progress.classList.add('d-none');
        progress.style.display = 'none';
    }
}

/* ── Validation error display ──────────────────────────────── */
function clearFieldErrors(form) {
    form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
    form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());
    // Clear per-field error placeholder divs (like #variant_id_error)
    form.querySelectorAll('[id$="_error"], [id$="_errors"]').forEach(el => {
        if (el.tagName === 'DIV') el.innerHTML = '';
    });
}

function renderFieldErrors(form, errors) {
    // errors is { field_name: ["msg", "msg2"], ... } from Laravel
    Object.entries(errors).forEach(([field, messages]) => {
        // Primary input — match by name, then by id
        const input = form.querySelector(`[name="${field}"]`)
                   || form.querySelector(`#${field}`);
        if (input) {
            input.classList.add('is-invalid');
            const feedback = document.createElement('div');
            feedback.className = 'invalid-feedback d-block';
            feedback.textContent = messages[0];
            input.parentElement?.appendChild(feedback);
        }

        // Also try to fill a dedicated error div if one exists,
        // e.g. <div id="variant_id_error"></div>
        const slot = form.querySelector(`#${field}_error`);
        if (slot) {
            slot.innerHTML = `<div class="text-danger fs-7 mt-1">${escapeHtml(messages[0])}</div>`;
        }

        // And the id without "_error" suffix
        const slot2 = document.getElementById(field);
        if (slot2 && slot2.tagName === 'DIV' && slot2 !== slot) {
            slot2.innerHTML = `<div class="text-danger fs-7 mt-1">${escapeHtml(messages[0])}</div>`;
        }
    });
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = String(str ?? '');
    return d.innerHTML;
}

</script>