
<div id="kt_app_header" class="app-header" data-kt-sticky="true" data-kt-sticky-activate="{default: true, lg: true}" data-kt-sticky-name="app-header-minimize" data-kt-sticky-offset="{default: '200px', lg: '0'}" data-kt-sticky-animation="false">
    <div class="app-container container-fluid d-flex align-items-stretch justify-content-between" id="kt_app_header_container">
        <div class="d-flex align-items-center d-lg-none ms-n3 me-1 me-md-2">
            <div class="btn btn-icon btn-active-color-primary w-35px h-35px" id="kt_app_sidebar_mobile_toggle">
                <i class="ki-duotone ki-abstract-14 fs-2 fs-md-1"><span class="path1"></span><span class="path2"></span></i>
            </div>
        </div>
        <div class="d-flex align-items-center flex-grow-1 flex-lg-grow-0">
            <a href="#" class="d-lg-none">
                <img alt="Logo" src="{{ getFaviconImage() }}" class="h-30px" />
            </a>
        </div>
        <div class="d-flex align-items-stretch justify-content-between flex-lg-grow-1" id="kt_app_header_wrapper">
            <div class="app-header-menu app-header-mobile-drawer align-items-stretch" data-kt-drawer="true" data-kt-drawer-name="app-header-menu" data-kt-drawer-activate="{default: true, lg: false}" data-kt-drawer-overlay="true" data-kt-drawer-width="250px" data-kt-drawer-direction="end" data-kt-drawer-toggle="#kt_app_header_menu_toggle" data-kt-swapper="true" data-kt-swapper-mode="{default: 'append', lg: 'prepend'}" data-kt-swapper-parent="{default: '#kt_app_body', lg: '#kt_app_header_wrapper'}">
                <!-- header -->
            </div>
            {{--
                resources/views/partials/sync-badge.blade.php
                Include once in your main layout header, e.g.:
                    @include('partials.sync-badge')

                On LOCAL machine  (IS_LOCAL_POS=true in .env):
                    - Badge polls /sync/frontend-status every 15 s
                    - Manual sync button fires /sync/trigger → artisan pos:sync
                    - Shows pending count + last synced time

                On REMOTE/cPanel (IS_LOCAL_POS=false or absent):
                    - Badge is hidden completely — no polling, no button
                    - The remote app has no local sync_status to display
            --}}

            @php
                $isLocal = filter_var(env('IS_LOCAL_POS', false), FILTER_VALIDATE_BOOLEAN);
            @endphp

            @if($isLocal)
            <div class="d-flex align-items-center gap-2 me-3" id="syncWidget">

                {{-- Status badge --}}
                <div class="d-flex flex-column align-items-end" style="line-height:1.1">
                    <div id="syncStatusBadge"
                        class="badge badge-secondary d-inline-flex align-items-center gap-1 px-2 py-1"
                        style="font-size:10px; min-width:72px; justify-content:center; cursor:default"
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom"
                        title="Checking sync status…">
                        <span class="spinner-border spinner-border-sm" style="width:8px;height:8px;border-width:1px"></span>
                        <span id="syncStatusText">CHECKING</span>
                    </div>
                    <span id="syncPendingCount" class="text-muted" style="font-size:9px; margin-top:2px"></span>
                </div>

                {{-- Manual sync button --}}
                <button id="manualSyncBtn"
                        type="button"
                        class="btn btn-sm btn-icon btn-light-primary"
                        onclick="triggerManualSync()"
                        title="Sync now"
                        data-bs-toggle="tooltip"
                        data-bs-placement="bottom">
                    <i id="syncBtnIcon" class="ki-duotone ki-arrows-circle fs-4">
                        <span class="path1"></span><span class="path2"></span>
                    </i>
                </button>

            </div>
            @endif

            {{-- ── Script (only rendered on local machine) ──────────────────────────── --}}
            @if($isLocal)
            @push('scripts')
                <script>
                (function () {
                    'use strict';

                    const STATUS_POLL_MS = 15000;
                    let _pollTimer       = null;
                    let _isSyncing       = false;

                    // ── Badge config ──────────────────────────────────────────────────────────
                    const STATUS_CONFIG = {
                        online:  { badgeClass: 'badge-success',   label: 'ONLINE',   icon: 'ki-wifi',           spin: false },
                        offline: { badgeClass: 'badge-warning',   label: 'OFFLINE',  icon: 'ki-wifi-slash',     spin: false },
                        syncing: { badgeClass: 'badge-primary',   label: 'SYNCING',  icon: 'ki-arrows-circle',  spin: true  },
                        error:   { badgeClass: 'badge-danger',    label: 'ERROR',    icon: 'ki-cross-circle',   spin: false },
                        unknown: { badgeClass: 'badge-secondary', label: 'CHECKING', icon: 'ki-information',    spin: false },
                    };

                    // ── Poll /sync/frontend-status ────────────────────────────────────────────
                    function updateSyncStatus() {
                        fetch('/sync/frontend-status', {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                            credentials: 'same-origin',
                        })
                        .then(r => r.ok ? r.json() : Promise.reject(r.status))
                        .then(renderBadge)
                        .catch(() => renderBadge({ status: 'offline', pending_count: 0 }));
                    }

                    function renderBadge(data) {
                        const status    = data.status || 'unknown';
                        const cfg       = STATUS_CONFIG[status] || STATUS_CONFIG.unknown;
                        const badge     = document.getElementById('syncStatusBadge');
                        const text      = document.getElementById('syncStatusText');
                        const pending   = document.getElementById('syncPendingCount');

                        if (!badge) return;

                        // Swap badge colour class
                        badge.className = badge.className
                            .replace(/badge-\w+/g, '')
                            .trim() + ' ' + cfg.badgeClass;

                        // Icon
                        const iconHtml = cfg.spin
                            ? `<span class="spinner-border spinner-border-sm" style="width:8px;height:8px;border-width:1.5px"></span>`
                            : `<i class="ki-duotone ${cfg.icon} fs-8"><span class="path1"></span><span class="path2"></span></i>`;

                        badge.innerHTML = `${iconHtml}<span id="syncStatusText">${cfg.label}</span>`;

                        // Tooltip: show last synced time or error
                        let tip = '';
                        if (data.last_synced_at) {
                            tip = 'Last sync: ' + formatRelative(data.last_synced_at);
                        }
                        if (data.last_error) {
                            tip += (tip ? ' · ' : '') + 'Error: ' + data.last_error;
                        }
                        if (tip) {
                            badge.setAttribute('title', tip);
                            // Re-init tooltip if KT bootstrap tooltips are loaded
                            if (window.bootstrap?.Tooltip) {
                                bootstrap.Tooltip.getInstance(badge)?.dispose();
                                new bootstrap.Tooltip(badge);
                            }
                        }

                        // Pending count sub-label
                        if (pending) {
                            if (data.pending_count > 0) {
                                pending.textContent = data.pending_count + ' pending';
                                pending.style.display = 'block';
                            } else {
                                pending.style.display = 'none';
                            }
                        }
                    }

                    // ── Manual sync trigger ───────────────────────────────────────────────────
                    window.triggerManualSync = function () {
                        if (_isSyncing) return;
                        _isSyncing = true;

                        const btn      = document.getElementById('manualSyncBtn');
                        const iconEl   = document.getElementById('syncBtnIcon');
                        if (btn)    btn.disabled = true;
                        if (iconEl) iconEl.className = 'ki-duotone ki-arrows-circle fs-4 spin-anim';

                        // Show syncing badge immediately
                        renderBadge({ status: 'syncing', pending_count: 0 });

                        fetch('/sync/trigger', {
                            method: 'POST',
                            headers: {
                                'Accept':       'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                            },
                            credentials: 'same-origin',
                        })
                        .then(r => r.json())
                        .then(data => {
                            if (data.success) {
                                // Poll after 3 s to give artisan time to run
                                setTimeout(updateSyncStatus, 3000);
                                if (typeof toastr !== 'undefined') {
                                    toastr.success(data.message || 'Sync triggered successfully.', 'Sync');
                                }
                            } else {
                                renderBadge({ status: 'error', pending_count: 0, last_error: data.error });
                                if (typeof toastr !== 'undefined') {
                                    toastr.error(data.error || 'Sync trigger failed.', 'Sync Error');
                                }
                            }
                        })
                        .catch(err => {
                            renderBadge({ status: 'error', pending_count: 0, last_error: err.message });
                            if (typeof toastr !== 'undefined') {
                                toastr.error('Sync request failed: ' + err.message, 'Sync Error');
                            }
                        })
                        .finally(() => {
                            _isSyncing = false;
                            if (btn)    btn.disabled = false;
                            if (iconEl) iconEl.className = 'ki-duotone ki-arrows-circle fs-4';
                        });
                    };

                    // ── Helpers ───────────────────────────────────────────────────────────────
                    function formatRelative(dateStr) {
                        if (!dateStr) return 'never';
                        const diff = Math.floor((Date.now() - new Date(dateStr).getTime()) / 1000);
                        if (diff < 60)   return diff + 's ago';
                        if (diff < 3600) return Math.floor(diff / 60) + 'm ago';
                        return Math.floor(diff / 3600) + 'h ago';
                    }

                    // ── Init ──────────────────────────────────────────────────────────────────
                    document.addEventListener('DOMContentLoaded', function () {
                        updateSyncStatus();
                        _pollTimer = setInterval(updateSyncStatus, STATUS_POLL_MS);
                    });

                    // Stop polling when tab is hidden, resume when visible (save requests)
                    document.addEventListener('visibilitychange', function () {
                        if (document.hidden) {
                            clearInterval(_pollTimer);
                        } else {
                            updateSyncStatus();
                            _pollTimer = setInterval(updateSyncStatus, STATUS_POLL_MS);
                        }
                    });
                })();
            </script>

            <style>
                @keyframes spin-anim { to { transform: rotate(360deg); } }
                .spin-anim { display: inline-block; animation: spin-anim 1s linear infinite; }
            </style>
            @endpush
            @endif
				
            <div class="app-navbar flex-shrink-0">
                <div class="app-navbar-item ms-1 ms-md-4">
                    <a href="#" class="btn btn-icon btn-custom btn-icon-muted btn-active-light btn-active-color-primary w-35px h-35px" data-kt-menu-trigger="{default:'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        <i class="ki-duotone ki-night-day theme-light-show fs-1"><span class="path1"></span><span class="path2"></span></i>
                        <i class="ki-duotone ki-moon theme-dark-show fs-1"><span class="path1"></span><span class="path2"></span></i>
                    </a>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-title-gray-700 menu-icon-gray-500 menu-active-bg menu-state-color fw-semibold py-4 fs-base w-150px" data-kt-menu="true" data-kt-element="theme-mode-menu">
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="light">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-night-day fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <span class="menu-title">{{__('auth._light')}}</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <span class="menu-title">{{__('auth._dark')}}</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-screen fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                                <span class="menu-title">{{__('auth._system')}}</span>
                            </a>
                        </div>
                    </div>
                </div>
                <div class="app-navbar-item ms-1 ms-md-4" id="kt_header_user_menu_toggle">
                    <div class="cursor-pointer symbol symbol-35px" data-kt-menu-trigger="{default: 'click', lg: 'hover'}" data-kt-menu-attach="parent" data-kt-menu-placement="bottom-end">
                        <img src="{{ getProfileImage() }}" class="rounded-3" alt="user" />
                    </div>
                    <div class="menu menu-sub menu-sub-dropdown menu-column menu-rounded menu-gray-800 menu-state-bg menu-state-color fw-semibold py-4 fs-6 w-275px" data-kt-menu="true">
                        <div class="menu-item px-3">
                            <div class="menu-content d-flex align-items-center px-3">
                                <div class="symbol symbol-50px me-5">
                                    <img alt="Logo" src="{{ getProfileImage() }}" />
                                </div>
                                <div class="d-flex flex-column">
                                    <div class="fw-bold d-flex align-items-center fs-5">{{ auth()->user()->name ?? __('payments.none') }}
                                        <span class="badge badge-light-success fw-bold fs-8 px-2 py-1 ms-2">{{__('auth._pro')}}</span>
                                    </div>
                                    <a href="#" class="fw-semibold text-muted text-hover-primary fs-7">{{ auth()->user()->email ?? __('payments.none') }}</a>
                                </div>
                            </div>
                        </div>
                        <div class="separator my-2"></div>
                        <div class="menu-item px-5">
                            <a href="{{ route('profile.edit') }}" class="enu-link px-5 fs-7">{{__('auth._my_profile')}}</a>
                        </div>
                        <div class="separator my-2"></div>
                        <div class="menu-item px-5">
                            <a  href="#" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"class="enu-link px-5 fs-7">{{__('auth._sign_out')}}</a>
                            <!-- Logout form (hidden) -->
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                @csrf
                            </form> 
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>