@include('layouts.partials.solen-palette')

<style>
    #mytask-layout.theme-indigo,
    [data-theme="dark"] #mytask-layout.theme-indigo,
    [data-theme="light"] #mytask-layout.theme-indigo {
        --primary-color: var(--solen-primary);
        --secondary-color: var(--solen-gold);
        --primary-gradient: var(--solen-gradient);
        --body-color: var(--solen-background);
        --card-color: var(--solen-card);
        --border-color: var(--solen-border);
        --color-100: #ffffff;
        --color-200: #ffffff;
        --color-300: #ffffff;
        --color-400: #cdbda8;
        --color-500: #a49686;
        --color-600: #7c6f60;
        --color-700: #5c4632;
        --color-800: #3f2e20;
        --color-900: #342416;
    }

    html,
    body {
        background: var(--solen-background) !important;
        color: var(--solen-foreground);
    }

    #mytask-layout {
        background: var(--solen-background) !important;
        min-height: 100vh;
    }

    .sidebar,
    [data-theme="dark"] .sidebar,
    [data-theme="light"] .sidebar {
        background: #000000 !important;
        background-clip: padding-box;
        border: 0 !important;
        outline: 0 !important;
        box-shadow: 0 24px 60px -28px rgba(0, 0, 0, 0.68);
    }

    .sidebar.sidebar-mini .menu-list .sub-menu,
    .sidebar.sidebar-mini .menu-list .m-link:hover span {
        background: #000000 !important;
    }

    .sidebar .brand-icon .logo-icon {
        background: rgba(255, 255, 255, 0.96);
        border-radius: 50%;
        box-shadow: 0 0 42px rgba(255, 246, 229, 0.46);
    }

    .sidebar .menu-list .m-link,
    .sidebar .menu-list .ms-link,
    .sidebar .form-switch label,
    .sidebar .sidebar-title {
        color: rgba(255, 255, 255, 0.88) !important;
    }

    .sidebar .brand-icon .logo-text {
        color: #ffffff !important;
    }

    .sidebar a:hover {
        text-decoration: none;
        color: #ffffff !important;
    }

    .sidebar .menu-list .m-link:hover,
    .sidebar .menu-list .m-link.active,
    .sidebar .menu-list .m-link.show,
    .sidebar .menu-list .ms-link:hover,
    .sidebar .menu-list .ms-link.active {
        background: transparent !important;
        color: #f19828 !important;
    }

    .sidebar .menu-list .m-link:hover *,
    .sidebar .menu-list .m-link.active *,
    .sidebar .menu-list .m-link.show *,
    .sidebar .menu-list .ms-link:hover *,
    .sidebar .menu-list .ms-link.active * {
        color: #f19828 !important;
    }

    .sidebar .menu-list .sub-menu::before,
    .sidebar .menu-list li[aria-expanded="true"] .sub-menu:before {
        background-color: rgba(241, 152, 40, 0.7) !important;
    }

    .main {
        background: #ffffff !important;
    }

    .main,
    .main .body,
    .body,
    .bg-light {
        background-color: #ffffff !important;
    }

    .card,
    .dropdown-menu,
    .modal-content {
        background-color: var(--solen-card) !important;
        border-color: var(--solen-border) !important;
        box-shadow: 0 18px 48px -34px rgba(52, 36, 22, 0.45);
    }

    .header .navbar {
        background-color: var(--solen-background) !important;
        border-color: var(--solen-border) !important;
        box-shadow: none !important;
    }

    .card .card-header,
    .modal-header,
    .premium-header,
    .premium-modal-header,
    .premium-lock-card,
    .premium-filter-card,
    .premium-card-header,
    .premium-table thead,
    .premium-widget,
    .premium-projects-card,
    .premium-stat-card,
    .premium-widget-card,
    .dashboard-widget .card-header,
    .ticket-dashboard-header,
    .ticket-section-header,
    .bg-dark,
    .bg-gradient-primary,
    .dashboard-tabs .nav-link.active,
    .premium-tabs .nav-link,
    .premium-tabs .nav-link.active,
    .premium-tabs .nav-link:hover,
    .nav-tabs .nav-link.active {
        background: var(--solen-gradient) !important;
        border-color: var(--solen-primary-border) !important;
        color: #ffffff !important;
    }

    .premium-header {
        box-shadow: 0 18px 48px -24px rgba(151, 76, 18, 0.55) !important;
    }

    .premium-header h1,
    .premium-lock-card,
    .premium-lock-card *,
    .premium-filter-card h5,
    .premium-card-header,
    .premium-card-header *,
    .premium-table thead th,
    .premium-widget,
    .premium-widget *,
    .premium-projects-card,
    .premium-projects-card *,
    .premium-stat-card,
    .premium-stat-card *,
    .premium-widget-card,
    .premium-widget-card *,
    .dashboard-widget .card-header,
    .dashboard-widget .card-header *,
    .premium-tabs .nav-link,
    .premium-tabs .nav-link *,
    .dashboard-tabs .nav-link.active,
    .dashboard-tabs .nav-link.active * {
        color: #ffffff !important;
    }

    .premium-tabs .nav-link {
        box-shadow: 0 8px 24px -16px rgba(151, 76, 18, 0.6) !important;
    }

    .premium-tabs .nav-link:not(.active),
    .dashboard-tabs .nav-link:not(.active) {
        color: #ffffff !important;
    }

    .dashboard-tabs .nav-link {
        background: var(--solen-secondary) !important;
        color: var(--solen-muted) !important;
    }

    .dashboard-tabs .nav-link:hover {
        background: var(--solen-gradient) !important;
        color: #ffffff !important;
    }

    .dashboard-tabs .nav-link,
    .dashboard-tabs .nav-link:hover {
        background: var(--solen-gradient) !important;
    }

    .dashboard-tabs .nav-link,
    .dashboard-tabs .nav-link *,
    .dashboard-tabs .nav-link:hover,
    .dashboard-tabs .nav-link:hover * {
        color: #ffffff !important;
    }

    [style*="#2c3e50"],
    [style*="#000000"],
    [style*="#34495e"],
    [style*="#2d3748"],
    [style*="#1a202c"] {
        border-color: var(--solen-primary-border) !important;
    }

    [style*="background"][style*="#2c3e50"],
    [style*="background"][style*="#000000"],
    [style*="background"][style*="#34495e"],
    [style*="background"][style*="#2d3748"],
    [style*="background"][style*="#1a202c"] {
        background: var(--solen-gradient) !important;
        color: #ffffff !important;
    }

    .table,
    .form-control,
    .form-select,
    .select2-container--default .select2-selection--single,
    .select2-container--default .select2-selection--multiple {
        border-color: var(--solen-border) !important;
        color: var(--solen-foreground);
    }

    .table-light,
    .table-hover > tbody > tr:hover > * {
        background-color: var(--solen-secondary) !important;
        color: var(--solen-foreground) !important;
    }

    .btn-primary,
    .btn-dark,
    .btn-success,
    .page-item.active .page-link {
        background: var(--solen-gradient) !important;
        border-color: transparent !important;
        color: #ffffff !important;
        box-shadow: 0 12px 30px -18px rgba(151, 76, 18, 0.55);
    }

    a,
    .page-link,
    .text-primary {
        color: var(--solen-primary-dark) !important;
    }

    .btn-primary a,
    .btn-dark a,
    .btn-success a,
    .sidebar a,
    .card-header a,
    .premium-header a,
    .premium-modal-header a {
        color: inherit !important;
    }

    /* Notifications dropdown: header sits on the warm gradient, so the
       "mark all as read" action and the count pill go translucent white. */
    .notifications .notification-count {
        background: rgba(255, 255, 255, 0.22);
        border-radius: 999px;
        padding: 0.05rem 0.45rem;
        font-size: 0.72rem;
        font-weight: 600;
    }

    .notifications .btn-mark-all-read {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.2rem 0.65rem;
        border: 1px solid rgba(255, 255, 255, 0.45);
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.16);
        color: #ffffff !important;
        font-size: 0.72rem;
        font-weight: 500;
        line-height: 1.5;
        white-space: nowrap;
        transition: background-color 0.15s ease, border-color 0.15s ease;
    }

    .notifications .btn-mark-all-read:hover:not(:disabled),
    .notifications .btn-mark-all-read:focus:not(:disabled) {
        background: rgba(255, 255, 255, 0.32);
        border-color: rgba(255, 255, 255, 0.7);
        box-shadow: none;
    }

    .notifications .btn-mark-all-read:disabled {
        opacity: 0.6;
        cursor: default;
    }

    /* Search box in the Salesforce Lightning style (icon left, clear button
       right, thin border, square-ish corners, brand glow on focus), in the
       Solen palette. Used by the notifications dropdown and the notifications
       page. Its own class, not .form-control, so the theme's input rules above
       stay out of it. */
    .sf-search {
        position: relative;
        display: flex;
        align-items: center;
        width: 100%;
    }

    .sf-search .sf-search-input {
        width: 100%;
        height: 2rem;
        padding: 0 2rem 0 2.1rem;
        border: 1px solid #c9c9c9;
        border-radius: 0.25rem;
        background: #ffffff;
        color: #181818;
        font-size: 0.8125rem;
        line-height: 1.875rem;
        box-shadow: none;
        transition: border-color 0.1s linear, box-shadow 0.1s linear;
        -webkit-appearance: none;
        appearance: none;
    }

    .sf-search .sf-search-input::placeholder {
        color: #747474;
    }

    .sf-search .sf-search-input:hover {
        border-color: #aeaeae;
    }

    .sf-search .sf-search-input:focus {
        outline: 0;
        border-color: var(--solen-primary);
        box-shadow: 0 0 0 1px var(--solen-primary), 0 0 3px 1px rgba(238, 143, 69, 0.45);
    }

    /* The browser's own clear "x" would sit on top of ours. */
    .sf-search .sf-search-input::-webkit-search-cancel-button,
    .sf-search .sf-search-input::-webkit-search-decoration {
        -webkit-appearance: none;
        appearance: none;
    }

    .sf-search .sf-search-icon {
        position: absolute;
        left: 0.75rem;
        color: #747474;
        font-size: 0.85rem;
        pointer-events: none;
    }

    .sf-search:focus-within .sf-search-icon {
        color: var(--solen-primary-deep);
    }

    .sf-search .sf-search-clear {
        position: absolute;
        right: 0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 1.5rem;
        height: 1.5rem;
        padding: 0;
        border: 0;
        border-radius: 50%;
        background: transparent;
        color: #747474 !important;
        font-size: 0.8rem;
        text-decoration: none;
        cursor: pointer;
    }

    .sf-search .sf-search-clear:hover,
    .sf-search .sf-search-clear:focus-visible {
        background: #f3f3f3;
        color: #181818 !important;
        outline: 0;
    }

    /* Where a search result matched. */
    mark.search-hit {
        padding: 0 0.1rem;
        border-radius: 2px;
        background: rgba(255, 193, 143, 0.55);
        color: inherit;
    }

    /* Read notifications stay in the dropdown list, just visually muted. */
    .notifications .notification-item.is-read {
        opacity: 0.6;
    }

    .notifications .notification-item.is-unread {
        background: rgba(151, 76, 18, 0.06);
        border-radius: 6px;
    }

    .notifications .btn-mark-one-read {
        flex: 0 0 auto;
        align-self: center;
        padding: 0.1rem 0.4rem;
        border: 1px solid rgba(151, 76, 18, 0.35);
        border-radius: 999px;
        background: transparent;
        color: var(--solen-primary-dark) !important;
        font-size: 0.72rem;
        line-height: 1.4;
    }

    .notifications .btn-mark-one-read:hover:not(:disabled),
    .notifications .btn-mark-one-read:focus:not(:disabled) {
        background: rgba(151, 76, 18, 0.12);
        box-shadow: none;
    }

    .notifications .btn-mark-one-read:disabled {
        opacity: 0.5;
        cursor: default;
    }
</style>
