<style>
    .operation-page-header {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 16px;
        margin-bottom: 16px;
    }

    .operation-page-title {
        margin: 0;
        font-size: 22px;
        font-weight: 700;
        color: #1f2937;
    }

    .operation-page-subtitle {
        margin: 4px 0 0;
        color: #6b7280;
        font-size: 14px;
    }

    .operation-summary {
        min-width: 150px;
        padding: 12px 16px;
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        background: #fff;
        text-align: right;
    }

    .operation-summary span {
        display: block;
        color: #6b7280;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: .04em;
    }

    .operation-summary strong {
        display: block;
        color: #111827;
        font-size: 24px;
        line-height: 1.1;
    }

    .operation-card {
        border: 1px solid #e5e7eb;
        border-radius: 8px;
        box-shadow: 0 8px 22px rgba(15, 23, 42, .04);
    }

    .operation-card .card-header {
        background: #fff;
        border-bottom: 1px solid #edf0f2;
        padding: 16px 18px;
    }

    .operation-card .card-title {
        margin: 0;
        color: #111827;
        font-size: 16px;
        font-weight: 700;
    }

    .operation-card .card-body {
        padding: 18px;
    }

    .operation-form label {
        color: #374151;
        font-size: 13px;
        font-weight: 600;
        margin-bottom: 6px;
    }

    .operation-form .form-control,
    .operation-form .form-select {
        border-color: #d1d5db;
        min-height: 40px;
        width: 100%;
    }

    .operation-form .select2-container {
        width: 100% !important;
    }

    .operation-form .select2-container--default .select2-selection--single {
        min-height: 40px;
        border: 1px solid #d1d5db;
        border-radius: 4px;
        display: flex;
        align-items: center;
        background: #fff;
    }

    .operation-form .select2-container--default .select2-selection--single .select2-selection__rendered {
        color: #495057;
        line-height: 38px;
        padding-left: 12px;
        padding-right: 32px;
        width: 100%;
    }

    .operation-form .select2-container--default .select2-selection--single .select2-selection__placeholder {
        color: #6c757d;
    }

    .operation-form .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 38px;
        right: 6px;
    }

    .operation-form .select2-container--default.select2-container--focus .select2-selection--single,
    .operation-form .select2-container--default.select2-container--open .select2-selection--single {
        border-color: #86b7fe;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
    }

    .operation-form .input-group-text {
        border-color: #d1d5db;
        background: #f9fafb;
        color: #6b7280;
        font-weight: 600;
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
        border-top-right-radius: 0;
        border-bottom-right-radius: 0;
    }

    .operation-form .cost-input-group .form-control {
        flex: 1 1 auto;
        min-width: 0;
        border-top-left-radius: 0;
        border-bottom-left-radius: 0;
    }

    .operation-actions {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 8px;
        margin-top: 18px;
    }

    .operation-actions .btn {
        min-width: 96px;
    }

    .operation-table {
        width: 100%;
        margin-bottom: 0;
    }

    .operation-table thead th {
        border-bottom: 1px solid #e5e7eb;
        color: #4b5563;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: .04em;
        white-space: nowrap;
    }

    .operation-table tbody td {
        vertical-align: middle;
        color: #1f2937;
    }

    .cost-value {
        font-variant-numeric: tabular-nums;
        font-weight: 600;
    }

    .action-link {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        background: #f9fafb;
        border: 1px solid #e5e7eb;
        cursor: pointer;
        transition: background .15s ease, border-color .15s ease;
    }

    .action-link:hover {
        background: #f3f4f6;
        border-color: #d1d5db;
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
        border: 1px solid #d1d5db;
        border-radius: 8px;
        background: #fff;
    }

    .tag-editor:focus-within {
        border-color: #0d6efd;
        box-shadow: 0 0 0 0.2rem rgba(13, 110, 253, 0.12);
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
        background: #1f2937;
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
            margin-top: 4px;
            flex-wrap: wrap;
        }
    }
</style>
