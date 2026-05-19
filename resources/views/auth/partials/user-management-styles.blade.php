<style>
    .user-management-page {
        background: #ffffff !important;
        color: #050505;
        --user-orange-border: rgba(238, 143, 69, 0.28);
        --user-orange-border-strong: rgba(238, 143, 69, 0.42);
        --user-orange-soft: rgba(255, 193, 143, 0.13);
        --user-ink: #1f2937;
        --user-muted: #64748b;
    }

    .user-management-page .body,
    .user-management-page .container-xxl {
        background: #ffffff !important;
    }

    .user-management-heading {
        background: transparent;
        border: 0;
        padding: 0;
        box-shadow: none;
        margin-bottom: 16px;
        text-align: left;
    }

    .user-management-heading h1,
    .user-management-heading h3,
    .user-management-heading h4,
    .user-management-page .operation-page-title {
        color: #1f2937 !important;
        font-size: 22px;
        font-weight: 700;
        margin: 0;
        letter-spacing: 0;
        width: 100%;
        text-align: left;
    }

    .user-management-heading.d-sm-flex h1 {
        width: auto;
        flex: 1 1 auto;
    }

    .user-management-page .operation-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: flex-start;
        gap: 16px;
        margin-bottom: 16px;
        text-align: left;
    }

    .user-management-page .operation-page-header > div:first-child {
        margin-right: auto;
    }

    .user-management-page .operation-page-subtitle {
        display: block;
        margin: 4px 0 0;
        color: #6b7280 !important;
        font-size: 14px;
    }

    .user-management-page .operation-summary {
        display: none;
    }

    .user-management-section {
        background: #ffffff;
        border-top: 1px solid var(--user-orange-border);
        border-radius: 0;
        padding: 1rem 0 1.15rem;
        box-shadow: none;
    }

    .user-management-section + .user-management-section {
        margin-top: 0.75rem;
    }

    .user-management-section-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.75rem;
        padding-bottom: 0.85rem;
        margin-bottom: 1rem;
        border-bottom: 1px solid rgba(238, 143, 69, 0.16);
    }

    .user-management-section-title {
        color: var(--user-ink) !important;
        font-size: 1.08rem;
        font-weight: 700;
        margin: 0;
        width: 100%;
        text-align: left;
    }

    .user-management-section-title i {
        color: var(--solen-primary-deep, #c8642d);
    }

    .user-management-section-body {
        background: #ffffff;
        padding: 0;
    }

    .user-management-page form .row {
        align-items: flex-end;
    }

    .user-management-page label,
    .user-management-page .form-label,
    .user-management-page .operation-form label {
        color: #374151 !important;
        font-size: 0.84rem;
        font-weight: 700;
        margin-bottom: 0.45rem;
    }

    .user-management-page .form-control,
    .user-management-page .form-select,
    .user-management-page .select2-container--default .select2-selection--single,
    .user-management-page .select2-container--default .select2-selection--multiple {
        background: #ffffff !important;
        border: 1px solid var(--user-orange-border) !important;
        border-radius: 999px !important;
        min-height: 42px;
        padding-left: 0.95rem;
        padding-right: 0.95rem;
    }

    .user-management-page .select2-container--default .select2-selection--multiple {
        padding: 0.25rem 0.45rem;
        border-radius: 16px !important;
    }

    .user-management-page textarea.form-control,
    .user-management-page select[multiple].form-control {
        border-radius: 16px !important;
    }

    .user-management-page .operation-actions,
    .user-management-page .form-actions-inline {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin-top: 0;
    }

    .user-management-page table thead,
    .user-management-page .table-premium thead {
        background: var(--user-orange-soft) !important;
    }

    .user-management-page table thead th,
    .user-management-page .table-premium thead th,
    .user-management-page .operation-table thead th {
        background: var(--user-orange-soft) !important;
        color: #7c2d12 !important;
        border-bottom: 1px solid var(--user-orange-border-strong) !important;
        font-size: 0.78rem;
        font-weight: 700;
        letter-spacing: 0.02em;
        text-transform: uppercase;
        padding: 0.85rem 0.75rem !important;
        white-space: nowrap;
    }

    .user-management-page table tbody td,
    .user-management-page .table-premium tbody td,
    .user-management-page .operation-table tbody td {
        border-bottom: 1px solid #eef2f7 !important;
        color: #1f2937 !important;
        padding: 0.8rem 0.75rem !important;
        vertical-align: middle;
    }

    .user-management-page table tbody tr:last-child td {
        border-bottom: 0 !important;
    }

    .user-management-page .table-bordered,
    .user-management-page .table-bordered td,
    .user-management-page .table-bordered th {
        border-color: var(--user-orange-border) !important;
    }

    .user-management-page tbody tr:hover {
        background: #fffaf5 !important;
    }

    .user-management-page .table-responsive,
    .user-management-page table {
        background: #ffffff !important;
    }

    .user-management-page .dataTables_wrapper {
        position: relative;
        width: 100%;
        clear: both;
        overflow-x: auto;
        overflow-y: visible;
    }

    .user-management-page .dataTables_wrapper > .row:first-child {
        align-items: center;
        row-gap: 0.75rem;
        margin: 0 0 0.9rem !important;
        padding: 0.65rem 0;
        border-bottom: 1px solid var(--user-orange-border);
    }

    .user-management-page .dataTables_wrapper > .row:first-child > [class*="col-"] {
        padding-left: 0 !important;
        padding-right: 0 !important;
    }

    .user-management-page div.dataTables_wrapper div.dataTables_length {
        text-align: left;
    }

    .user-management-page div.dataTables_wrapper div.dataTables_length label,
    .user-management-page div.dataTables_wrapper div.dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        color: #64748b !important;
        font-size: 0.84rem;
        font-weight: 700 !important;
        white-space: nowrap;
    }

    .user-management-page div.dataTables_wrapper div.dataTables_length select {
        min-height: 36px;
        border: 1px solid var(--user-orange-border) !important;
        border-radius: 999px !important;
        margin: 0 0.25rem;
    }

    .user-management-page div.dataTables_wrapper div.dataTables_filter {
        float: none !important;
        position: static !important;
        text-align: right !important;
        width: 100%;
    }

    .user-management-page div.dataTables_wrapper div.dataTables_filter input {
        width: min(280px, 100%) !important;
        min-height: 38px;
        margin-left: 0 !important;
        border: 1px solid var(--user-orange-border) !important;
        border-radius: 999px !important;
        padding: 0.45rem 0.9rem !important;
        background: #ffffff !important;
    }

    .user-management-page .dataTables_wrapper table.dataTable {
        clear: both;
        margin-top: 0 !important;
        margin-bottom: 0.75rem !important;
        width: 100% !important;
    }

    .user-management-page .dataTables_wrapper > .row:last-child {
        align-items: center;
        margin-top: 0.75rem !important;
        color: #64748b;
        font-size: 0.84rem;
    }

    .user-management-page .dataTables_wrapper .pagination .page-link {
        border-color: var(--user-orange-border) !important;
        color: #7c2d12 !important;
        border-radius: 999px;
        margin-left: 0.2rem;
    }

    .user-management-page .action-link,
    .user-management-page td a[data-toggle="tooltip"] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid var(--user-orange-border);
    }

    .user-management-page .btn-primary,
    .user-management-page .btn-dark,
    .user-management-page .btn-premium {
        background: var(--solen-gradient, linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%)) !important;
        border-color: transparent !important;
        border-radius: 999px !important;
        color: #ffffff !important;
        box-shadow: 0 12px 30px -18px rgba(151, 76, 18, 0.55) !important;
    }

    .user-management-page .btn-primary:hover,
    .user-management-page .btn-dark:hover,
    .user-management-page .btn-premium:hover {
        background: var(--solen-gradient, linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%)) !important;
        border-color: transparent !important;
        transform: translateY(-1px) !important;
    }

    .user-management-page .btn-danger:not(.swal2-confirm),
    .user-management-page .btn-cancel,
    .user-management-page .btn-outline-secondary {
        background: #ffffff !important;
        border: 1px solid var(--user-orange-border) !important;
        border-radius: 999px !important;
        color: #7c2d12 !important;
        box-shadow: none !important;
    }

    .user-management-page .form-control:focus,
    .user-management-page .form-select:focus {
        border-color: var(--solen-primary, #ee8f45) !important;
        box-shadow: 0 0 0 0.2rem rgba(238, 143, 69, 0.16) !important;
    }

    @media (max-width: 767.98px) {
        .user-management-page .dataTables_wrapper > .row:first-child {
            gap: 0.75rem;
        }

        .user-management-page div.dataTables_wrapper div.dataTables_length,
        .user-management-page div.dataTables_wrapper div.dataTables_filter {
            text-align: left !important;
        }

        .user-management-page div.dataTables_wrapper div.dataTables_filter label {
            align-items: flex-start;
            flex-direction: column;
            width: 100%;
        }

        .user-management-page div.dataTables_wrapper div.dataTables_filter input {
            width: 100% !important;
        }
    }
</style>
