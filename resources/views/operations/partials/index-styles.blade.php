<style>
    .operation-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 18px;
    }

    .operation-page-title {
        margin: 0;
        color: #1f2937;
        font-size: 22px;
        font-weight: 700;
        letter-spacing: 0;
    }

    .operation-page-subtitle {
        margin: 4px 0 0;
        color: #64748b;
        font-size: 14px;
    }

    .operation-summary {
        min-width: 150px;
        padding: 10px 16px;
        border: 1px solid rgba(238, 143, 69, 0.28);
        border-radius: 999px;
        background: #ffffff;
        text-align: right;
    }

    .operation-summary span {
        display: block;
        color: #9a3412;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .operation-summary strong {
        display: block;
        color: #1f2937;
        font-size: 21px;
        line-height: 1.1;
    }

    .operation-card,
    .customer-section-card {
        background: #ffffff !important;
        border: 0 !important;
        border-top: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 0 !important;
        box-shadow: none !important;
        overflow: visible;
    }

    .operation-card .card-header,
    .customer-section-card .card-header {
        background: transparent !important;
        border: 0 !important;
        border-bottom: 1px solid rgba(238, 143, 69, 0.16) !important;
        padding: 0.85rem 0 !important;
    }

    .operation-card .card-title,
    .customer-section-card .card-title {
        margin: 0;
        color: #1f2937 !important;
        font-size: 1.08rem;
        font-weight: 700;
    }

    .operation-card .card-body,
    .customer-section-card .card-body {
        background: #ffffff !important;
        padding: 1rem 0 1.35rem !important;
    }

    .operation-form label {
        color: #374151;
        font-size: 13px;
        font-weight: 700;
        margin-bottom: 6px;
    }

    .operation-form .form-control,
    .operation-form .form-select,
    .operation-form textarea,
    .operation-form select,
    .operation-form input[type="file"] {
        width: 100%;
        min-height: 42px;
        border: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 999px !important;
        background: #ffffff !important;
        color: #1f2937;
        padding-left: 0.95rem;
        padding-right: 0.95rem;
    }

    .operation-form textarea,
    .operation-form select[multiple],
    .operation-form .select2-container--default .select2-selection--multiple {
        border-radius: 16px !important;
    }

    .operation-form .form-control:focus,
    .operation-form .form-select:focus {
        border-color: var(--solen-primary, #ee8f45) !important;
        box-shadow: 0 0 0 0.2rem rgba(238, 143, 69, 0.16) !important;
    }

    .operation-form .select2-container {
        width: 100% !important;
    }

    .operation-form .select2-container--default .select2-selection--single,
    .operation-form .select2-container--default .select2-selection--multiple {
        min-height: 42px;
        border: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 999px !important;
        display: flex;
        align-items: center;
        background: #fff;
    }

    .operation-form .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        line-height: 40px;
        padding-left: 12px;
        padding-right: 32px;
        width: 100%;
    }

    .operation-form .select2-container--default .select2-selection__arrow {
        height: 40px;
        right: 8px;
    }

    .operation-form .select2-container--default.select2-container--focus .select2-selection--single,
    .operation-form .select2-container--default.select2-container--open .select2-selection--single {
        border-color: var(--solen-primary, #ee8f45) !important;
        box-shadow: 0 0 0 0.2rem rgba(238, 143, 69, 0.16) !important;
    }

    .operation-form .input-group-text {
        border-color: rgba(238, 143, 69, 0.28) !important;
        background: rgba(255, 193, 143, 0.13);
        color: #9a3412;
        font-weight: 700;
    }

    .operation-form .cost-input-group {
        display: flex;
        flex-wrap: nowrap;
        align-items: stretch;
        width: 100%;
    }

    .operation-form .cost-input-group .input-group-text {
        flex: 0 0 42px;
        justify-content: center;
        border-top-left-radius: 999px;
        border-bottom-left-radius: 999px;
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .operation-form .cost-input-group .form-control {
        flex: 1 1 auto;
        min-width: 0;
        border-top-left-radius: 0 !important;
        border-bottom-left-radius: 0 !important;
    }

    .operation-actions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        margin-top: 0;
    }

    .operation-actions .btn,
    .operation-card .btn,
    .customer-section-card .btn,
    .operation-page-header .btn {
        min-width: 96px;
        border-radius: 999px !important;
    }

    .operation-page-header .btn-outline-secondary,
    .operation-actions .btn-outline-secondary {
        background: #ffffff !important;
        border: 1px solid rgba(238, 143, 69, 0.28) !important;
        color: #7c2d12 !important;
    }

    .operation-table {
        width: 100%;
        margin-bottom: 0;
        background: #ffffff;
    }

    .operation-table thead th,
    table.datatable thead th {
        background: rgba(255, 193, 143, 0.13) !important;
        color: #7c2d12 !important;
        border-bottom: 1px solid rgba(238, 143, 69, 0.42) !important;
        font-size: 0.78rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .02em;
        padding: 0.85rem 0.75rem !important;
        white-space: nowrap;
    }

    .operation-table tbody td,
    table.datatable tbody td {
        border-bottom: 1px solid #eef2f7 !important;
        color: #1f2937;
        padding: 0.8rem 0.75rem !important;
        vertical-align: middle;
    }

    table.datatable tbody tr:hover {
        background: #fffaf5 !important;
    }

    div.dataTables_wrapper {
        clear: both;
        overflow-x: auto;
        overflow-y: visible;
    }

    div.dataTables_wrapper > .row:first-child {
        align-items: center;
        row-gap: 0.75rem;
        margin: 0 0 0.9rem !important;
        padding: 0.65rem 0;
        border-bottom: 1px solid rgba(238, 143, 69, 0.28);
    }

    div.dataTables_wrapper div.dataTables_filter {
        float: none !important;
        position: static !important;
        text-align: right !important;
        width: 100%;
    }

    div.dataTables_wrapper div.dataTables_length label,
    div.dataTables_wrapper div.dataTables_filter label {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        color: #64748b !important;
        font-size: 0.84rem;
        font-weight: 700 !important;
        white-space: nowrap;
    }

    div.dataTables_wrapper div.dataTables_length select,
    div.dataTables_wrapper div.dataTables_filter input {
        min-height: 38px;
        border: 1px solid rgba(238, 143, 69, 0.28) !important;
        border-radius: 999px !important;
        background: #ffffff !important;
    }

    div.dataTables_wrapper div.dataTables_filter input {
        width: min(280px, 100%) !important;
        margin-left: 0 !important;
        padding: 0.45rem 0.9rem !important;
    }

    div.dataTables_wrapper .pagination .page-link {
        border-color: rgba(238, 143, 69, 0.28) !important;
        border-radius: 999px;
        color: #7c2d12 !important;
        margin-left: 0.2rem;
    }

    .cost-value {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }

    .action-link,
    table.datatable td a[data-toggle="tooltip"] {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 999px;
        background: #ffffff;
        border: 1px solid rgba(238, 143, 69, 0.28);
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
    }

    .action-link:hover,
    table.datatable td a[data-toggle="tooltip"]:hover {
        background: #fffaf5;
        border-color: rgba(238, 143, 69, 0.42);
        text-decoration: none;
    }

    .empty-state {
        padding: 32px 16px;
        color: #6b7280;
        text-align: center;
    }

    .tag-editor {
        min-height: 48px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding: 8px 12px;
        border: 1px solid rgba(238, 143, 69, 0.28);
        border-radius: 16px;
        background: #fff;
    }

    .tag-editor:focus-within {
        border-color: var(--solen-primary, #ee8f45);
        box-shadow: 0 0 0 0.2rem rgba(238, 143, 69, 0.16);
    }

    .tag-editor input {
        border: none;
        outline: none;
        flex: 1 1 140px;
        min-width: 140px;
        padding: 6px 0;
    }

    .tag-chip {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 6px 10px;
        border-radius: 999px;
        background: var(--solen-gradient, linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%));
        color: #fff;
        font-size: 13px;
        font-weight: 600;
    }

    .tag-chip button {
        border: 0;
        background: transparent;
        color: #fff;
        cursor: pointer;
        line-height: 1;
        padding: 0;
        font-size: 14px;
    }

    @media (max-width: 767px) {
        .operation-page-header {
            display: block;
        }

        .operation-summary {
            margin-top: 12px;
            text-align: left;
        }

        .operation-actions {
            flex-wrap: wrap;
        }

        div.dataTables_wrapper div.dataTables_length,
        div.dataTables_wrapper div.dataTables_filter {
            text-align: left !important;
        }

        div.dataTables_wrapper div.dataTables_filter label {
            align-items: flex-start;
            flex-direction: column;
            width: 100%;
        }

        div.dataTables_wrapper div.dataTables_filter input {
            width: 100% !important;
        }
    }
</style>
