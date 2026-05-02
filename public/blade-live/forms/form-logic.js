/**
 * formLogic (internal)
 * --------------------
 * All AJAX mechanics for LiveBlade. Vanilla JS only — zero jQuery,
 * zero SweetAlert2, zero external imports beyond LiveBladeResponse.
 *
 * @version 2.0.0
 * @license MIT
 */

import LiveBladeResponse from '../responses/liveblade-responses.js';

// ─── Utilities ───────────────────────────────────────────────────────────────

/**
 * Read the CSRF token from either a meta tag or a hidden input.
 * @returns {string}
 */
function getCsrf() {
    const meta  = document.querySelector('meta[name="csrf-token"]');
    const input = document.querySelector('input[name="_token"]');
    return (meta?.getAttribute('content') || input?.value || '').trim();
}

/**
 * Return a debounced version of fn that fires after `wait` ms of silence.
 * @param {Function} fn
 * @param {number} wait
 * @returns {Function}
 */
function debounce(fn, wait = 300) {
    let timer;
    return function (...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), wait);
    };
}

/**
 * Sanitize a value for safe insertion as text (not HTML).
 * Prefer textContent. This is a belt-and-suspenders helper for
 * places where we must build a string attribute value.
 * @param {*} val
 * @returns {string}
 */
function esc(val) {
    if (val === null || val === undefined) return '';
    return String(val)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

// ─── formLogic ───────────────────────────────────────────────────────────────

const formLogic = {

    // ── Error display ────────────────────────────────────────────────────────

    handleError(error) {
        console.error('[LiveBlade] Error:', error);
        LiveBladeResponse.displayErrorMessage(error);
    },

    // ── Delete ───────────────────────────────────────────────────────────────

    /**
     * Send a DELETE request to deleteUrl.
     * Resolves true on success, false on failure.
     * @param {string} deleteUrl
     * @returns {Promise<boolean>}
     */
    loopDeleteForms(deleteUrl) {
        return fetch(deleteUrl, {
            method: 'DELETE',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                LiveBladeResponse.reloadOrRedirect(response);
                return true;
            }
            LiveBladeResponse.displayErrorMessage(response.message || 'Delete failed');
            return false;
        })
        .catch(err => {
            this.handleError(err);
            return false;
        });
    },

    // ── Update (loop rows) ────────────────────────────────────────────────────

    /**
     * PUT/PATCH an existing resource.
     * Resolves true on success, false on validation/server error.
     * @param {Object} data       Must include _method (PUT|PATCH)
     * @param {string} updateUrl
     * @returns {Promise<boolean>}
     */
    loopUpdateForms(data, updateUrl) {
        const uniqueId = this._lastSegment(updateUrl);

        return fetch(updateUrl, {
            method: 'POST', // Laravel method spoofing via _method field
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-HTTP-Method-Override': data._method || 'PUT',
            },
            body: JSON.stringify(data),
        })
        .then(async res => {
            const json = await res.json();

            if (res.status === 422) {
                if (json.errors) {
                    LiveBladeResponse.displayValidationErrorsForInstances(json.errors, uniqueId);
                }
                return false;
            }

            if (!res.ok) {
                this.handleError(json);
                return false;
            }

            if (json.success) {
                LiveBladeResponse.reloadOrRedirect(json);
                return true;
            }

            LiveBladeResponse.displayErrorMessage(json.message || 'Update failed');
            return false;
        })
        .catch(err => {
            this.handleError(err);
            return false;
        });
    },

    // ── Create (generic form submit) ─────────────────────────────────────────

    /**
     * POST a new resource.
     * Resolves true on success, false otherwise.
     * @param {Object} data   Must include routeName and _method
     * @returns {Promise<boolean>}
     */
    submitFormEntities(data) {
        return fetch(data.routeName, {
            method: data._method || 'POST',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': data._token || getCsrf(),
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify(data),
        })
        .then(async res => {
            const json = await res.json();

            if (res.status === 422) {
                if (json.errors) LiveBladeResponse.displayValidationErrors(json.errors);
                return false;
            }

            if (!res.ok) {
                this.handleError(json);
                return false;
            }

            if (json.success) {
                LiveBladeResponse.reloadOrRedirect(json);
                return true;
            }

            LiveBladeResponse.displayErrorMessage(json.message || 'Submission failed');
            return false;
        })
        .catch(err => {
            this.handleError(err);
            return false;
        });
    },

    // ── Status toggle ────────────────────────────────────────────────────────

    /**
     * POST a status update (active / inactive toggle).
     * @param {string} updateUrl
     * @param {0|1}    selectedStatus
     */
    loopUpdateStatusForms(updateUrl, selectedStatus) {
        const body = new URLSearchParams({
            status: selectedStatus,
            _token: getCsrf(),
        });

        fetch(updateUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Accept': 'application/json' },
            body,
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                LiveBladeResponse.reloadOrRedirect(response);
            } else {
                LiveBladeResponse.displayErrorMessage(response.message || 'Status update failed');
            }
        })
        .catch(err => this.handleError(err));
    },

    // ── Image upload ─────────────────────────────────────────────────────────

    /**
     * Upload a file via multipart/form-data POST.
     * @param {File}   file
     * @param {string} uploadRoute
     * @param {string} fileInputName   The form field name expected by the server
     */
    beginUploadImage(file, uploadRoute, fileInputName) {
        const formData = new FormData();
        formData.append(fileInputName, file);

        fetch(uploadRoute, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'X-CSRF-TOKEN': getCsrf(), 'Accept': 'application/json' },
            body: formData,
        })
        .then(async res => {
            const text = await res.text();
            let json;
            try { json = JSON.parse(text); }
            catch { throw new Error(`Non-JSON response: ${text.slice(0, 120)}`); }

            if (!res.ok) throw json;

            if (json.success) {
                LiveBladeResponse.displaySuccessMessage(json.message || 'File uploaded');
            } else {
                LiveBladeResponse.displayErrorMessage(json.message || 'Upload failed');
            }
        })
        .catch(err => this.handleError(err));
    },

    // ── Action-driven GET call ───────────────────────────────────────────────

    /**
     * Fire a GET request to elementUrl and react to the JSON response.
     * Resolves true on success, false otherwise.
     * @param {string} elementUrl
     * @returns {Promise<boolean>}
     */
    actionDrivenCall(elementUrl) {
        return fetch(elementUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-CSRF-TOKEN': getCsrf(),
                'Accept': 'application/json',
            },
        })
        .then(res => res.json())
        .then(response => {
            if (response.success) {
                LiveBladeResponse.reloadOrRedirect(response);
                return true;
            }
            LiveBladeResponse.displayErrorMessage(response.message || 'Action failed');
            return false;
        })
        .catch(err => {
            this.handleError(err);
            return false;
        });
    },

    // ── Client-side DOM search ───────────────────────────────────────────────

    /**
     * Filter visible table rows by matching a text query against all cell content.
     * Use this only for small datasets (<200 rows). For larger tables use
     * beginServerSearch() instead.
     * @param {string} inputId   id of the <input> element
     * @param {string} tableId   id of the <table> element
     */
    beginTableSearch(inputId, tableId) {
        const input = document.getElementById(inputId);
        if (!input) return;

        input.addEventListener('keyup', function () {
            const q = this.value.toLowerCase();
            document.querySelectorAll(`#${tableId} tbody tr`).forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(q) ? '' : 'none';
            });
        });
    },

    // ── Server-side (DB) search with pagination ───────────────────────────────

    /**
     * Wire up a search input to hit a server endpoint and re-render table rows
     * plus a pagination bar inside the given container.
     *
     * Expected server response shape:
     * {
     *   success: true,
     *   data: [ { /* row fields *\/ } ],
     *   pagination: {
     *     current_page: 1, last_page: 5,
     *     per_page: 15, total: 73,
     *     from: 1, to: 15,
     *   },
     *   row_template: 'function(row){ return `<tr>...</tr>`; }' // optional — see docs
     * }
     *
     * @param {Object} opts
     * @param {string}   opts.inputId          id of the search <input>
     * @param {string}   opts.tableId          id of the <table>
     * @param {string}   opts.searchUrl        GET endpoint (e.g. /api/users/search)
     * @param {Function} opts.rowRenderer      fn(rowData) → HTML string for one <tr>
     * @param {string}   [opts.paginationId]   id of a <div> where pagination is injected
     * @param {number}   [opts.debounceMs=350]
     */
    beginServerSearch({ inputId, tableId, searchUrl, rowRenderer, paginationId, debounceMs = 350 }) {
        const input = document.getElementById(inputId);
        if (!input) return;

        let currentPage = 1;

        const doSearch = (page = 1) => {
            currentPage = page;
            const q   = input.value.trim();
            const url = new URL(searchUrl, window.location.origin);
            url.searchParams.set('q', q);
            url.searchParams.set('page', page);

            fetch(url.toString(), {
                method: 'GET',
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': getCsrf() },
            })
            .then(res => res.json())
            .then(response => {
                if (!response.success) {
                    LiveBladeResponse.displayErrorMessage(response.message || 'Search failed');
                    return;
                }
                this._renderTableRows(tableId, response.data, rowRenderer);
                if (paginationId && response.pagination) {
                    this._renderPagination(paginationId, response.pagination, p => doSearch(p));
                }
            })
            .catch(err => this.handleError(err));
        };

        input.addEventListener('keyup', debounce(() => doSearch(1), debounceMs));
        // Also trigger on clear
        input.addEventListener('search', () => doSearch(1));
    },

    /**
     * Inject pagination UI into a container.
     * @param {string}   containerId
     * @param {Object}   p   { current_page, last_page, from, to, total }
     * @param {Function} onPageClick  fn(pageNumber)
     */
    _renderPagination(containerId, p, onPageClick) {
        const container = document.getElementById(containerId);
        if (!container) return;

        if (p.last_page <= 1) { container.innerHTML = ''; return; }

        const pages  = [];
        const radius = 2; // pages each side of current

        // Always show first page
        pages.push(1);

        const start = Math.max(2, p.current_page - radius);
        const end   = Math.min(p.last_page - 1, p.current_page + radius);

        if (start > 2)              pages.push('…');
        for (let i = start; i <= end; i++) pages.push(i);
        if (end < p.last_page - 1) pages.push('…');

        // Always show last page
        if (p.last_page > 1) pages.push(p.last_page);

        const items = pages.map(pg => {
            if (pg === '…') return `<li class="page-item disabled"><span class="page-link">…</span></li>`;
            const active = pg === p.current_page ? 'active' : '';
            return `<li class="page-item ${active}">
                        <button class="page-link" data-lb-page="${pg}">${pg}</button>
                    </li>`;
        });

        container.innerHTML = `
            <nav aria-label="Table navigation">
                <ul class="pagination pagination-sm mb-0 flex-wrap">
                    <li class="page-item ${p.current_page === 1 ? 'disabled' : ''}">
                        <button class="page-link" data-lb-page="${p.current_page - 1}">&laquo;</button>
                    </li>
                    ${items.join('')}
                    <li class="page-item ${p.current_page === p.last_page ? 'disabled' : ''}">
                        <button class="page-link" data-lb-page="${p.current_page + 1}">&raquo;</button>
                    </li>
                </ul>
                <small class="text-muted ms-2">
                    Showing ${esc(p.from)}–${esc(p.to)} of ${esc(p.total)}
                </small>
            </nav>`;

        container.querySelectorAll('[data-lb-page]').forEach(btn => {
            btn.addEventListener('click', () => {
                const pg = parseInt(btn.dataset.lbPage, 10);
                if (!isNaN(pg) && pg >= 1 && pg <= p.last_page) onPageClick(pg);
            });
        });
    },

    /**
     * Re-render tbody rows using a rowRenderer function.
     * @param {string}   tableId
     * @param {Array}    rows
     * @param {Function} rowRenderer fn(rowData) → HTML string
     */
    _renderTableRows(tableId, rows, rowRenderer) {
        const tbody = document.querySelector(`#${tableId} tbody`);
        if (!tbody) return;

        if (!rows || rows.length === 0) {
            const cols = document.querySelectorAll(`#${tableId} thead th`).length || 1;
            tbody.innerHTML = `<tr><td colspan="${cols}" class="text-center text-muted py-4">No results found.</td></tr>`;
            return;
        }

        tbody.innerHTML = rows.map(rowRenderer).join('');
    },

    // ── Card search (client-side) ────────────────────────────────────────────

    /**
     * Filter card-style elements by matching a data attribute or title text.
     * @param {string} inputId
     * @param {string} cardSelector       CSS selector for each card
     * @param {string} attributeName      data-* attribute to match against
     * @param {string} titleSelector      CSS selector of the title inside each card
     */
    beginCardSearch(inputId, cardSelector, attributeName, titleSelector) {
        const searchBar = document.getElementById(inputId);
        if (!searchBar) return;

        const cards = document.querySelectorAll(cardSelector);

        searchBar.addEventListener('input', function () {
            const q = this.value.toLowerCase();
            cards.forEach(card => {
                const attrVal  = (card.getAttribute(attributeName) || '').toLowerCase();
                const titleVal = (card.querySelector(titleSelector)?.textContent || '').toLowerCase();
                card.style.display = (attrVal.includes(q) || titleVal.includes(q)) ? '' : 'none';
            });
        });
    },

    // ── Table filter (dropdown) ──────────────────────────────────────────────

    /**
     * Filter table rows by matching a dropdown value against a row's data attribute.
     * @param {string} dropdownSelector   CSS selector for the <select>
     * @param {string} tableSelector      CSS selector for the <table>
     * @param {string} dataAttribute      dataset key on each <tr> (without "data-")
     */
    beginTableFilter(dropdownSelector, tableSelector, dataAttribute) {
        const dropdown = document.querySelector(dropdownSelector);
        if (!dropdown) return;

        dropdown.addEventListener('change', function () {
            const val = this.value.toLowerCase();
            document.querySelectorAll(`${tableSelector} tbody tr`).forEach(row => {
                const rowVal = (row.dataset[dataAttribute] || '').toLowerCase();
                row.style.display = (!val || rowVal === val) ? '' : 'none';
            });
        });
    },

    // ── Internal helpers ─────────────────────────────────────────────────────

    /**
     * Return the last path segment of a URL string.
     * e.g. '/promotions/42' → '42'
     * @param {string} url
     * @returns {string}
     */
    _lastSegment(url) {
        return url.split('/').filter(Boolean).pop() || '';
    },
};

export default formLogic;