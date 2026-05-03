/**
 * LiveBladeResponse
 * -----------------
 * Handles all UI feedback (toasts, error modals, validation) using
 * ONLY Bootstrap 5 — zero CDN imports, zero SweetAlert2, zero jQuery.
 *
 * @version 2.0.0
 * @license MIT
 */

const LiveBladeResponse = (() => {

    // ─── DOM bootstrap helpers ──────────────────────────────────────────────

    /**
     * Inject the shared modal + toast container into <body> exactly once.
     * Idempotent — safe to call multiple times.
     */
    function _ensureShell() {
        if (document.getElementById('lb-shell')) return;

        const shell = document.createElement('div');
        shell.id = 'lb-shell';
        shell.innerHTML = `
            <!-- LiveBlade toast container -->
            <div id="lb-toast-container"
                 class="toast-container position-fixed top-0 end-0 p-3"
                 style="z-index:1090"></div>

            <!-- LiveBlade error / info modal -->
            <div class="modal fade" id="lb-modal" tabindex="-1"
                 aria-labelledby="lb-modal-title" aria-modal="true" role="dialog">
                <div class="modal-dialog modal-lg modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header" id="lb-modal-header">
                            <h5 class="modal-title" id="lb-modal-title">Error</h5>
                            <button type="button" class="btn-close"
                                    data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body" id="lb-modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary"
                                    data-bs-dismiss="modal">Close</button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- LiveBlade inline styles (scoped, no external sheet needed) -->
            <style id="lb-styles">
                #lb-toast-container .lb-toast { min-width: 280px; }
                #lb-toast-container .lb-toast .toast-body {
                    display: flex; align-items: center; gap: 10px;
                    font-size: .875rem; font-weight: 500;
                }
                #lb-toast-container .lb-toast .lb-icon { font-size: 1.1rem; flex-shrink: 0; }

                /* Error detail table inside modal */
                #lb-modal .lb-error-table {
                    width: 100%; border-collapse: collapse;
                    font-size: .85rem; margin-bottom: 0;
                }
                #lb-modal .lb-error-table th,
                #lb-modal .lb-error-table td {
                    padding: 8px 12px;
                    border: 1px solid var(--bs-border-color, #dee2e6);
                    vertical-align: top;
                    word-break: break-word;
                }
                #lb-modal .lb-error-table th {
                    width: 22%; background: var(--bs-light, #f8f9fa);
                    font-weight: 600; color: var(--bs-body-color, #212529);
                }
                #lb-modal .lb-error-table td { color: var(--bs-secondary-color, #6c757d); }
                #lb-modal .lb-error-table tr:nth-child(even) td {
                    background: var(--bs-tertiary-bg, #f8f9fa);
                }
                /* Trace inner table */
                #lb-modal .lb-trace-table {
                    width: 100%; border-collapse: collapse;
                    font-size: .78rem; margin-top: 8px;
                }
                #lb-modal .lb-trace-table th,
                #lb-modal .lb-trace-table td {
                    padding: 5px 8px;
                    border: 1px solid var(--bs-border-color, #dee2e6);
                    vertical-align: top;
                }
                #lb-modal .lb-trace-table th { background: var(--bs-light, #f8f9fa); font-weight: 600; }
                #lb-modal .lb-trace-table td:nth-child(1) { width: 4%; text-align: center; }
                #lb-modal .lb-trace-table td:nth-child(2) { width: 58%; font-family: monospace; }
                #lb-modal .lb-trace-table td:nth-child(3) { width: 10%; text-align: center; }
                #lb-modal .lb-trace-table td:nth-child(4) { width: 28%; font-family: monospace; }

                /* Validation error spans */
                .lb-field-error {
                    display: block; font-size: .8rem;
                    color: var(--bs-danger, #dc3545); margin-top: 3px;
                }
            </style>
        `;
        document.body.appendChild(shell);
    }

    /**
     * Safely escape HTML to prevent XSS when inserting user/server data.
     * @param {*} value
     * @returns {string}
     */
    function _esc(value) {
        if (value === null || value === undefined) return '';
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    // ─── Toast ───────────────────────────────────────────────────────────────

    /**
     * Show a Bootstrap 5 toast.
     * @param {'success'|'error'|'warning'|'info'} type
     * @param {string} message
     * @param {number} [delay=3000] ms before auto-hide
     */
    function _toast(type, message, delay = 3000) {
        _ensureShell();

        const icons = {
            success: '✓',
            error:   '✕',
            warning: '⚠',
            info:    'ℹ',
        };
        const classes = {
            success: 'text-bg-success',
            error:   'text-bg-danger',
            warning: 'text-bg-warning',
            info:    'text-bg-info',
        };

        const el = document.createElement('div');
        el.className = `toast lb-toast align-items-center border-0 ${classes[type] ?? 'text-bg-secondary'}`;
        el.setAttribute('role', 'alert');
        el.setAttribute('aria-live', 'assertive');
        el.setAttribute('aria-atomic', 'true');
        el.innerHTML = `
            <div class="toast-body">
                <span class="lb-icon" aria-hidden="true">${icons[type] ?? ''}</span>
                <span>${_esc(message)}</span>
                <button type="button" class="btn-close btn-close-white ms-auto"
                        data-bs-dismiss="toast" aria-label="Dismiss"></button>
            </div>`;

        document.getElementById('lb-toast-container').appendChild(el);

        const bsToast = new bootstrap.Toast(el, { delay, autohide: true });
        bsToast.show();
        el.addEventListener('hidden.bs.toast', () => el.remove());
    }

    // ─── Modal ───────────────────────────────────────────────────────────────

    /**
     * Open the shared modal with arbitrary HTML content.
     * @param {string} title
     * @param {string} bodyHtml   — must be pre-sanitized / built by this module
     * @param {'danger'|'warning'|'info'|'success'} [variant='danger']
     */
    function _modal(title, bodyHtml, variant = 'danger') {
        _ensureShell();

        const variantClasses = {
            danger:  'text-bg-danger',
            warning: 'text-bg-warning',
            info:    'text-bg-info',
            success: 'text-bg-success',
        };

        const header = document.getElementById('lb-modal-header');
        const titleEl = document.getElementById('lb-modal-title');
        const body = document.getElementById('lb-modal-body');

        // Reset header classes
        header.className = `modal-header ${variantClasses[variant] ?? variantClasses.danger}`;
        const closeBtn = header.querySelector('.btn-close');
        if (closeBtn) closeBtn.className = 'btn-close btn-close-white';

        titleEl.textContent = title;
        body.innerHTML = bodyHtml; // bodyHtml is built internally — always escaped

        const modal = new bootstrap.Modal(document.getElementById('lb-modal'));
        modal.show();
    }

    // ─── Public API ──────────────────────────────────────────────────────────

    return {

        // ── Navigation ──────────────────────────────────────────────────────

        redirectOrFreshPage(url) {
            window.location.href = url;
        },

        /**
         * Central response router — decides whether to reload a component,
         * do a full redirect, or just show a message.
         * @param {Object} response   Standard LiveBlade JSON response shape
         */
        reloadOrRedirect(response) {
            if (!response) return false;

            if (response.redirect === '/dashboard') return true;

            if (response.success && response.reload && !response.refresh) {
                this.displaySuccessMessage(response.message);
                this.fetchAndReloadComponent(response);
                return true;
            }

            if (response.success && !response.reload && response.refresh) {
                this.redirectOrFreshPage(response.redirect);
                return true;
            }

            if (response.success && !response.reload && !response.refresh) {
                this.displaySuccessMessage(response.message || 'Operation successful');
                return true;
            }

            this.displayErrorMessage(response.message || 'Operation failed');
            return false;
        },

        // ── Component reload ─────────────────────────────────────────────────

        /**
         * Fetch a fresh render of a blade component and swap its innerHTML.
         * @param {Object} response   Must include redirect, componentId
         */
        fetchAndReloadComponent(response) {
            if (!response.componentId) return;

            const container = document.getElementById(response.componentId);
            if (!container) {
                this.displayErrorMessage(`Component #${response.componentId} not found in DOM`);
                return;
            }

            const csrfInput = document.querySelector('input[name="_token"], meta[name="csrf-token"]');
            const csrf = csrfInput
                ? (csrfInput.value || csrfInput.getAttribute('content'))
                : '';

            const url = `${response.redirect}?bladeFileToReload=${encodeURIComponent(response.componentId)}`;

            fetch(url, {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'X-CSRF-TOKEN': csrf },
            })
            .then(res => {
                if (!res.ok) throw new Error(`HTTP ${res.status}`);
                return res.text();
            })
            .then(html => {
                const doc = new DOMParser().parseFromString(html, 'text/html');
                const fresh = doc.getElementById(response.componentId);
                if (fresh) {
                    container.innerHTML = fresh.innerHTML;
                    // Re-initialise any page scripts that rely on the new DOM
                    if (typeof window.initializeComponentScripts === 'function') {
                        window.initializeComponentScripts();
                    }
                } else {
                    this.displayErrorMessage(`Could not locate #${response.componentId} in the fetched response.`);
                }
            })
            .catch(err => {
                console.error('[LiveBlade] Component reload failed:', err);
                this.displayErrorMessage(`Component reload failed: ${err.message}`);
            });
        },

        // ── Feedback messages ────────────────────────────────────────────────

        /**
         * Show a success toast.
         * @param {string} [message]
         */
        displaySuccessMessage(message = 'Operation successful') {
            _toast('success', message);
        },

        /**
         * Show a warning toast.
         * @param {string} [message]
         */
        displayWarningMessage(message = 'Warning') {
            _toast('warning', message);
        },

        /**
         * Show an info toast.
         * @param {string} [message]
         */
        displayInfoMessage(message = 'Info') {
            _toast('info', message);
        },

        /**
         * Display a server error inside a Bootstrap modal.
         * Accepts either a plain string or a structured error object from Laravel.
         *
         * Structured shape: { message, exception, file, line, trace: [{file,line,function}] }
         *
         * @param {string|Object} error
         */
        displayErrorMessage(error) {
            _ensureShell();

            let bodyHtml;

            if (typeof error === 'string') {
                // Plain message — still safe because we're wrapping in textContent below
                bodyHtml = `<p class="mb-0 text-danger fw-semibold">${_esc(error)}</p>`;

            } else if (typeof error === 'object' && error !== null) {
                // Structured Laravel error object
                let rows = '';

                if (error.message)   rows += `<tr><th>Message</th><td>${_esc(error.message)}</td></tr>`;
                if (error.exception) rows += `<tr><th>Exception</th><td>${_esc(error.exception)}</td></tr>`;
                if (error.file)      rows += `<tr><th>File</th><td><code>${_esc(error.file)}</code></td></tr>`;
                if (error.line)      rows += `<tr><th>Line</th><td>${_esc(error.line)}</td></tr>`;

                if (Array.isArray(error.trace) && error.trace.length) {
                    let traceRows = error.trace.map((t, i) => `
                        <tr>
                            <td>${i}</td>
                            <td><code>${_esc(t.file || 'unknown')}</code></td>
                            <td>${_esc(t.line || '–')}</td>
                            <td><code>${_esc(t.function || 'unknown')}</code></td>
                        </tr>`).join('');

                    rows += `
                        <tr>
                            <th>Trace</th>
                            <td>
                                <table class="lb-trace-table">
                                    <thead>
                                        <tr><th>#</th><th>File</th><th>Line</th><th>Function</th></tr>
                                    </thead>
                                    <tbody>${traceRows}</tbody>
                                </table>
                            </td>
                        </tr>`;
                }

                bodyHtml = rows
                    ? `<table class="lb-error-table"><tbody>${rows}</tbody></table>`
                    : `<p class="mb-0 text-danger fw-semibold">${_esc(JSON.stringify(error))}</p>`;

            } else {
                bodyHtml = `<p class="mb-0 text-danger fw-semibold">An unexpected error occurred.</p>`;
            }

            _modal('Error', bodyHtml, 'danger');
        },

        // ── Validation errors ────────────────────────────────────────────────

        /**
         * Remove all previously displayed inline validation error spans.
         */
        clearValidationErrors() {
            document.querySelectorAll('.lb-field-error').forEach(el => el.remove());
        },

        /**
         * Display Laravel validation errors next to their fields.
         * Fields are located by id="fieldName".
         *
         * @param {Object} errors   { field: ['message', ...], ... }
         */
        displayValidationErrors(errors) {
            this.clearValidationErrors();
            let first = true;

            for (const [field, messages] of Object.entries(errors)) {
                const input = document.getElementById(field);
                if (!input) continue;

                const span = document.createElement('span');
                span.className = 'lb-field-error';
                span.textContent = messages[0]; // first message only
                input.parentNode.insertBefore(span, input.nextSibling);

                if (first) { input.focus(); first = false; }
            }

            if (!first) _toast('error', 'Please fix the validation errors below.');
        },

        /**
         * Same as displayValidationErrors but appends a unique suffix to each
         * field id — for loop-rendered edit forms where each row has its own
         * instance (e.g. id="edit_name1", "edit_name2" …).
         *
         * @param {Object} errors
         * @param {string|number} instanceId   Suffix appended to each field id
         */
        displayValidationErrorsForInstances(errors, instanceId) {
            this.clearValidationErrors();
            let first = true;

            for (const [field, messages] of Object.entries(errors)) {
                const input = document.getElementById(`${field}${instanceId}`);
                if (!input) continue;

                const span = document.createElement('span');
                span.className = 'lb-field-error';
                span.textContent = messages[0];
                input.parentNode.insertBefore(span, input.nextSibling);

                if (first) { input.focus(); first = false; }
            }

            if (!first) _toast('error', 'Please fix the validation errors below.');
        },
    };

})();

export default LiveBladeResponse;