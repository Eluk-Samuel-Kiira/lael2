
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

                {{-- Manual sync button 
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
                </button> --}}

            </div>
            @endif

            {{-- ── Script (only rendered on local machine) ──────────────────────────── --}}
            @if($isLocal)
            @push('scripts')
            <script>
                (function () {
                    'use strict';
                    const POLL_MS = 60000; // Check every 10 seconds

                    function updateSyncStatus() {
                        fetch('/sync/frontend-status', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        })
                        .then(r => r.ok ? r.json() : Promise.reject())
                        .then(renderBadge)
                        .catch(() => renderBadge({ status: 'offline', pending_count: 0 }));
                    }

                    function renderBadge(data) {
                        const status = data.status || 'unknown';
                        const badge = document.getElementById('syncStatusBadge');
                        const pending = document.getElementById('syncPendingCount');
                        if (!badge) return;

                        // Colors
                        const colors = {
                            online: 'badge-success',
                            offline: 'badge-warning',
                            syncing: 'badge-primary',
                            error: 'badge-danger'
                        };
                        
                        // Icons
                        const icons = {
                            online: '<i class="ki-duotone ki-wifi fs-8"></i>',
                            offline: '<i class="ki-duotone ki-wifi-slash fs-8"></i>',
                            syncing: '<span class="spinner-border spinner-border-sm" style="width:8px;height:8px"></span>',
                            error: '<i class="ki-duotone ki-cross-circle fs-8"></i>'
                        };

                        badge.className = `badge ${colors[status] || 'badge-secondary'} d-inline-flex align-items-center gap-1 px-2 py-1`;
                        badge.innerHTML = `${icons[status] || ''} <span id="syncStatusText">${status.toUpperCase()}</span>`;

                        // Pending Count
                        if (pending) {
                            if (data.pending_count > 0) {
                                pending.textContent = `${data.pending_count} pending`;
                                pending.style.display = 'block';
                            } else {
                                pending.style.display = 'none';
                            }
                        }

                        // Tooltip
                        let tip = `Status: ${status}`;
                        if (data.last_synced_at) tip += `\nLast Sync: ${new Date(data.last_synced_at).toLocaleString()}`;
                        if (data.last_error) tip += `\nError: ${data.last_error}`;
                        badge.setAttribute('title', tip);
                    }

                    // Start polling
                    document.addEventListener('DOMContentLoaded', function () {
                        updateSyncStatus();
                        setInterval(updateSyncStatus, POLL_MS);
                    });
                })();
            </script>
            <style>
                .spin-anim { animation: spin-anim 1s linear infinite; }
                @keyframes spin-anim { to { transform: rotate(360deg); } }
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
                                <span class="enu-link px-5 fs-7">{{__('auth._light')}}</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="dark">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-moon fs-2"><span class="path1"></span><span class="path2"></span></i>
                                </span>
                                <span class="enu-link px-5 fs-7">{{__('auth._dark')}}</span>
                            </a>
                        </div>
                        <div class="menu-item px-3 my-0">
                            <a href="#" class="menu-link px-3 py-2" data-kt-element="mode" data-kt-value="system">
                                <span class="menu-icon" data-kt-element="icon">
                                    <i class="ki-duotone ki-screen fs-2"><span class="path1"></span><span class="path2"></span><span class="path3"></span><span class="path4"></span></i>
                                </span>
                                <span class="enu-link px-5 fs-7">{{__('auth._system')}}</span>
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


<!-- All tables to industry standard -->
<style>
    /* ============================================
       Industry Standard Table Sizes
       Used by GitHub, Stripe, Vercel, and Bootstrap
       ============================================ */
    
    /* Standard table cell padding - Industry standard */
    .table:not(.table-bordered):not(.table-borderless) > :not(caption) > * > * {
        padding: 0.75rem 1rem !important;
        vertical-align: middle;
    }
    
    /* Standard font sizes */
    .table td, 
    .table th {
        font-size: 0.875rem; /* 14px - standard */
        line-height: 1.5;
    }
    
    /* Headers - slightly smaller and uppercase */
    .table thead th {
        font-size: 0.75rem; /* 12px */
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #6c757d;
        padding: 0.875rem 1rem !important;
    }
    
    /* Standard badge sizing */
    .table .badge {
        font-size: 0.75rem; /* 12px */
        font-weight: 500;
        padding: 0.25rem 0.5rem;
        line-height: 1.2;
        border-radius: 6px;
    }
    
    /* Standard button sizing */
    .table .btn-sm {
        padding: 0.25rem 0.75rem;
        font-size: 0.75rem;
        line-height: 1.5;
        border-radius: 6px;
    }
    
    /* Icon-only buttons */
    .table .btn-icon.btn-sm {
        width: 28px;
        height: 28px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Standard select dropdown */
    .table select.form-select-sm {
        min-width: 95px;
        font-size: 0.75rem;
        padding: 0.25rem 0.75rem;
        line-height: 1.5;
        border-radius: 6px;
    }
    
    /* Standard avatar size */
    .table .symbol {
        width: 36px !important;
        height: 36px !important;
    }
    
    .table .symbol-50px {
        width: 36px !important;
        height: 36px !important;
    }
    
    /* Standard spacing between elements */
    .table .gap-2 {
        gap: 0.5rem !important;
    }
    
    /* Standard form checks */
    .table .form-check-input {
        width: 1rem;
        height: 1rem;
        margin-top: 0;
    }
    
    /* Row hover effect - standard */
    .table tbody tr {
        transition: background-color 0.15s ease;
    }
    
    .table tbody tr:hover {
        background-color: rgba(0, 0, 0, 0.02);
    }
    
    /* Responsive - tablet */
    @media (max-width: 992px) {
        .table > :not(caption) > * > * {
            padding: 0.6rem 0.75rem !important;
        }
        
        .table .symbol,
        .table .symbol-50px {
            width: 32px !important;
            height: 32px !important;
        }
    }
    
    /* Responsive - mobile */
    @media (max-width: 768px) {
        .table > :not(caption) > * > * {
            padding: 0.5rem 0.6rem !important;
        }
        
        .table td, 
        .table th {
            font-size: 0.8125rem; /* 13px */
        }
        
        .table .btn-sm span:not(.d-none) {
            display: none;
        }
        
        .table .btn-sm i {
            margin: 0 !important;
        }
        
        .table .symbol,
        .table .symbol-50px {
            width: 28px !important;
            height: 28px !important;
        }
        
        .table .badge {
            font-size: 0.7rem;
            padding: 0.2rem 0.4rem;
        }
    }
    
    /* Dark mode support (if needed) */
    @media (prefers-color-scheme: dark) {
        .table tbody tr:hover {
            background-color: rgba(255, 255, 255, 0.02);
        }
    }
</style>

<style>
    /* Reduce toolbar height */
    .app-toolbar.py-3.py-lg-6 {
        padding-top: 0.25rem !important;
        padding-bottom: 0.25rem !important;
    }
    
    /* Reduce heading size */
    .page-heading {
        font-size: 1.1rem !important;
        margin: 0 !important;
    }
    
    /* Reduce search input height */
    .input-group {
        height: 42px !important;
    }
    
    .form-control,
    .input-group-text {
        padding: 0.2rem 0.5rem !important;
        height: 42px !important;
        font-size: 1.0rem !important;
    }
    
    /* Reduce button size */
    .btn-primary {
        padding: 0.2rem 0.75rem !important;
        font-size: 1.0rem !important;
        height: 42px !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 0.25rem !important;
    }
    
    /* Reduce icon size */
    .btn-primary i,
    .input-group-text i {
        font-size: 1.0rem !important;
    }
    
    /* Reduce gap between elements */
    .d-flex.gap-3 {
        gap: 0.5rem !important;
    }
    
    /* Make search input narrower */
    .w-sm-250px {
        width: 180px !important;
    }
    
    /* Responsive - keep readable on mobile */
    @media (max-width: 768px) {
        .w-sm-250px {
            width: 100% !important;
        }
        
        .page-heading {
            font-size: 1rem !important;
        }
    }
</style>
