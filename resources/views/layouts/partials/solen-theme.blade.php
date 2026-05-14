<style>
    :root {
        --solen-background: #fffdf6;
        --solen-foreground: #342416;
        --solen-card: #ffffff;
        --solen-muted: #7c6f60;
        --solen-border: #eadfce;
        --solen-primary: #f07a24;
        --solen-primary-dark: #be4d18;
        --solen-secondary: #fff6e5;
        --solen-gradient: linear-gradient(135deg, #f7c948 0%, #f07a24 48%, #b93f1d 100%);
        --solen-shadow: 0 24px 60px -28px rgba(151, 76, 18, 0.45);
    }

    #mytask-layout.theme-indigo,
    [data-theme="dark"] #mytask-layout.theme-indigo,
    [data-theme="light"] #mytask-layout.theme-indigo {
        --primary-color: #f07a24;
        --secondary-color: #f7c948;
        --primary-gradient: var(--solen-gradient);
        --body-color: var(--solen-background);
        --card-color: var(--solen-card);
        --border-color: var(--solen-border);
        --color-100: #fff8ec;
        --color-200: #fff1dc;
        --color-300: #f5e5cf;
        --color-400: #cdbda8;
        --color-500: #a49686;
        --color-600: #7c6f60;
        --color-700: #5c4632;
        --color-800: #3f2e20;
        --color-900: #342416;
    }

    body {
        background: var(--solen-background) !important;
        color: var(--solen-foreground);
    }

    #mytask-layout {
        background:
            radial-gradient(circle at top left, rgba(247, 201, 72, 0.2), transparent 34%),
            var(--solen-background) !important;
        min-height: 100vh;
    }

    .sidebar,
    [data-theme="dark"] .sidebar,
    [data-theme="light"] .sidebar {
        background: var(--solen-gradient) !important;
        box-shadow: var(--solen-shadow);
    }

    .sidebar.sidebar-mini .menu-list .sub-menu,
    .sidebar.sidebar-mini .menu-list .m-link:hover span {
        background: var(--solen-gradient) !important;
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
        color: rgba(255, 255, 255, 0.9) !important;
    }

    .sidebar .menu-list .m-link:hover,
    .sidebar .menu-list .m-link.active,
    .sidebar .menu-list .ms-link:hover,
    .sidebar .menu-list .ms-link.active {
        background: rgba(255, 255, 255, 0.18) !important;
        color: #ffffff !important;
    }

    .main {
        background: transparent !important;
    }

    .header .navbar,
    .card,
    .dropdown-menu,
    .modal-content {
        background-color: var(--solen-card) !important;
        border-color: var(--solen-border) !important;
        box-shadow: 0 18px 48px -34px rgba(52, 36, 22, 0.45);
    }

    .card .card-header,
    .modal-header,
    .premium-header,
    .premium-modal-header,
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
        border-color: rgba(240, 122, 36, 0.18) !important;
        color: #ffffff !important;
    }

    .premium-header {
        box-shadow: 0 18px 48px -24px rgba(151, 76, 18, 0.55) !important;
    }

    .premium-header h1,
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
        border-color: rgba(240, 122, 36, 0.18) !important;
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
</style>
