<script>
(function () {
    'use strict';

    if (!window.LEAVE_ROUTES) {
        console.error('[leave-page] LEAVE_ROUTES missing.');
        return;
    }

    const ROUTES = window.LEAVE_ROUTES;
    const CSRF   = document.querySelector('meta[name="csrf-token"]')?.content || '';

    // ── View toggle ──────────────────────────────────────
    function setView(which) {
        const cal = document.getElementById('leaveCalendarView');
        const lst = document.getElementById('leaveListView');
        const btnCal = document.getElementById('viewCalendarBtn');
        const btnLst = document.getElementById('viewListBtn');

        if (!cal || !lst) return;

        if (which === 'list') {
            cal.classList.add('d-none');
            lst.classList.remove('d-none');
            btnCal?.classList.remove('active');
            btnLst?.classList.add('active');
            window.LeaveList?.reload?.();
        } else {
            lst.classList.add('d-none');
            cal.classList.remove('d-none');
            btnLst?.classList.remove('active');
            btnCal?.classList.add('active');
        }
    }

    document.getElementById('viewCalendarBtn')?.addEventListener('click', () => setView('calendar'));
    document.getElementById('viewListBtn')?.addEventListener('click', () => setView('list'));

    // ── Calendar ─────────────────────────────────────────
    function initCalendar() {
        const el = document.getElementById('kt_calendar');
        if (!el || !window.FullCalendar) return;

        const calendar = new FullCalendar.Calendar(el, {
            initialView: 'dayGridMonth',
            headerToolbar: {
                left: 'prev,next today',
                center: 'title',
                right: 'dayGridMonth,timeGridWeek,listMonth',
            },
            events: (info, success, failure) => {
                const url = `${ROUTES.calendar}?start=${info.startStr}&end=${info.endStr}`;
                fetch(url, { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } })
                    .then(r => r.json())
                    .then(success)
                    .catch(failure);
            },
            eventClick: (info) => {
                const id = parseInt(info.event.id, 10);
                if (id && window.LeaveDrawer) window.LeaveDrawer.open(id);
            },
            height: 700,
            dayMaxEvents: 3,
        });

        calendar.render();
        return calendar;
    }

    document.addEventListener('DOMContentLoaded', () => {
        setTimeout(initCalendar, 100);
    });

    // ── Create modal ─────────────────────────────────────
    const modal = document.getElementById('kt_modal_add_leave');
    const form  = document.getElementById('kt_modal_add_leave_form');
    if (!modal || !form) return;

    // Toggle custom_type
    form.querySelector('[name="leave_type"]')?.addEventListener('change', function () {
        const isOther = this.value === 'other';
        const f = document.getElementById('custom_type_field');
        if (f) f.style.display = isOther ? '' : 'none';
        const inp = form.querySelector('[name="custom_type"]');
        if (inp) inp.required = isOther;
    });

    // Days preview
    function updateDaysPreview() {
        const s = form.querySelector('[name="start_date"]')?.value;
        const e = form.querySelector('[name="end_date"]')?.value;
        const out = document.getElementById('total_days_preview');
        if (!s || !e || !out) return;
        const start = new Date(s), end = new Date(e);
        const days = Math.round((end - start) / 86400000) + 1;
        out.textContent = days > 0 ? days : 0;
    }
    form.querySelector('[name="start_date"]')?.addEventListener('change', updateDaysPreview);
    form.querySelector('[name="end_date"]')?.addEventListener('change', updateDaysPreview);

    // Submit
    const btn = modal.querySelector('.btn-primary');
    btn?.addEventListener('click', async (e) => {
        e.preventDefault();
        const fd = new FormData(form);

        setBtn(btn, true);

        // ── Clear previous errors ────────────────────────
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        try {
            const res = await fetch(ROUTES.store, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': CSRF,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: fd,
            });

            const json = await res.json().catch(() => ({}));

            // ── Handle validation errors (422 OR any response with `errors`) ──
            if (res.status === 422 || (json.errors && Object.keys(json.errors).length > 0)) {
                renderValidationErrors(form, json.errors || {}, json.message);

                setBtn(btn, false);
                return;
            }

            // ── Non-validation error ──
            if (!res.ok || json.success === false) {
                window.toastr?.error?.(json.message || 'Create failed');
                setBtn(btn, false);
                return;
            }

            // ── Success ──
            window.toastr?.success?.(json.message || 'Leave request submitted.');
            setBtn(btn, false);
            bootstrap.Modal.getInstance(modal)?.hide();
            form.reset();
            window.LeaveList?.reload?.();

        } catch (err) {
            console.error('[create-leave] failed:', err);
            window.toastr?.error?.(err.message || 'Something went wrong.');
            setBtn(btn, false);
        }
    });

    function renderValidationErrors(form, errors, fallbackMessage) {
        // Clear previous
        form.querySelectorAll('.is-invalid').forEach(el => el.classList.remove('is-invalid'));
        form.querySelectorAll('.invalid-feedback').forEach(el => el.remove());

        const seen = new Set();

        Object.entries(errors).forEach(([field, msgs]) => {
            const baseField = field.replace(/\.\d+$/, '').replace(/\[\]$/, '');
            if (seen.has(baseField)) return;
            seen.add(baseField);

            const input =
                form.querySelector(`[name="${CSS.escape(field)}"]`) ||
                form.querySelector(`[name="${CSS.escape(baseField)}"]`) ||
                form.querySelector(`[name="${CSS.escape(baseField)}[]"]`);

            const companion = form.querySelector(`#${CSS.escape(baseField)}`);

            if (input) input.classList.add('is-invalid');

            let holder = companion;
            if (!holder && input) {
                const parent = input.closest('.fv-row') || input.parentNode;
                holder = parent?.querySelector(':scope > .invalid-feedback')
                    || parent?.querySelector('.invalid-feedback');
                if (!holder && parent) {
                    holder = document.createElement('div');
                    holder.className = 'invalid-feedback d-block';
                    parent.appendChild(holder);
                }
            }

            if (holder) {
                holder.textContent = msgs[0];
                holder.classList.add('invalid-feedback', 'd-block');
                holder.style.display = '';
            }
        });

        const firstError = Object.values(errors)[0]?.[0];
        window.toastr?.error?.(firstError || fallbackMessage || 'Please fix the highlighted fields.');

        const firstInvalid =
            form.querySelector('input.is-invalid, select.is-invalid, textarea.is-invalid')
            || form.querySelector('.is-invalid');
        if (firstInvalid) {
            firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            if (typeof firstInvalid.focus === 'function') firstInvalid.focus({ preventScroll: true });
        }
    }

    function setBtn(b, loading) {
        if (!b) return;
        const label = b.querySelector('.indicator-label');
        const progress = b.querySelector('.indicator-progress');
        if (loading) { b.disabled = true; if (label) label.style.display = 'none'; if (progress) progress.style.display = 'inline'; }
        else { b.disabled = false; if (label) label.style.display = 'inline'; if (progress) progress.style.display = 'none'; }
    }
})();
</script>