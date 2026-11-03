{{-- The milestone schedule editor, laid out like a Salesforce Lightning related
     list (card header + count + action, compact bordered grid, footer) in the
     Solen palette. Everything is scoped under .ms-card: the operations form
     styles turn every input into a 42px pill, which is what truncated the
     values in this grid, so the rules below out-rank them inside it only. --}}
<style>
    .ms-card {
        --ms-primary: var(--solen-primary, #ee8f45);
        --ms-primary-dark: #c8642d;
        --ms-ink: #7c2d12;
        --ms-tint: rgba(255, 193, 143, 0.13);
        --ms-line: #f0e4da;
        --ms-field-line: #e2d3c6;
        --ms-muted: #8a7768;

        background: #ffffff;
        border: 1px solid var(--ms-line);
        border-radius: 8px;
        box-shadow: 0 2px 2px rgba(124, 45, 18, 0.04);
        overflow: hidden;
    }

    .ms-card-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 1rem;
        padding: 0.85rem 1rem;
        border-bottom: 1px solid var(--ms-line);
        flex-wrap: wrap;
    }

    .ms-card-title {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        min-width: 0;
    }

    .ms-card-title h5 {
        margin: 0;
        font-size: 0.95rem;
        font-weight: 700;
        color: #1f2937;
    }

    .ms-card-title p {
        margin: 0.1rem 0 0;
        font-size: 0.78rem;
        color: var(--ms-muted);
    }

    .ms-count {
        font-weight: 400;
        color: var(--ms-muted);
    }

    .ms-card-icon {
        flex: 0 0 auto;
        width: 32px;
        height: 32px;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        color: #ffffff;
        font-size: 1rem;
        background: var(--solen-gradient, linear-gradient(135deg, #ffc18f 0%, #ee8f45 56%, #c8642d 100%));
    }

    .ms-btn-neutral {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        height: 32px;
        padding: 0 0.9rem;
        border: 1px solid var(--ms-field-line);
        border-radius: 4px;
        background: #ffffff;
        color: var(--ms-ink);
        font-size: 0.82rem;
        font-weight: 600;
        transition: background-color 0.15s, border-color 0.15s;
    }

    .ms-btn-neutral:hover,
    .ms-btn-neutral:focus-visible {
        background: #fff6ee;
        border-color: var(--ms-primary);
        outline: none;
    }

    .ms-alert {
        display: flex;
        align-items: center;
        gap: 0.5rem;
        margin: 0;
        padding: 0.6rem 1rem;
        background: #fef2f2;
        border-bottom: 1px solid #fecaca;
        color: #b91c1c;
        font-size: 0.82rem;
        font-weight: 600;
    }

    .ms-table-wrap {
        overflow-x: auto;
    }

    .ms-table {
        width: 100%;
        min-width: 1180px;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 0.82rem;
    }

    .ms-table thead th {
        background: var(--ms-tint);
        color: var(--ms-ink);
        font-size: 0.7rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
        padding: 0.55rem 0.5rem;
        border-bottom: 1px solid rgba(238, 143, 69, 0.35);
        border-right: 1px solid var(--ms-line);
    }

    .ms-table thead th:last-child,
    .ms-table tbody td:last-child {
        border-right: 0;
    }

    .ms-table tbody td {
        padding: 0.4rem 0.5rem;
        vertical-align: top;
        border-bottom: 1px solid var(--ms-line);
        border-right: 1px solid var(--ms-line);
        background: #ffffff;
    }

    .ms-table tbody tr:last-child td {
        border-bottom: 0;
    }

    .ms-table tbody tr:hover td {
        background: #fffaf5;
    }

    .ms-col-num { width: 40px; text-align: center; }
    .ms-col-label { min-width: 190px; }
    .ms-col-trigger { min-width: 210px; }
    .ms-col-type { min-width: 160px; }
    .ms-col-amount { width: 130px; min-width: 130px; }
    .ms-col-base { min-width: 170px; }
    .ms-col-payee { min-width: 140px; }
    .ms-col-status { width: 118px; min-width: 118px; }
    .ms-col-action { width: 52px; text-align: center; }

    .ms-row-number {
        display: inline-block;
        line-height: 32px;
        color: var(--ms-muted);
        font-variant-numeric: tabular-nums;
    }

    /* Out-ranks `.operation-form .form-control` (pill, 42px) inside the grid. */
    .operation-form .ms-table .form-control,
    .operation-form .ms-table .form-select {
        min-height: 32px !important;
        height: 32px;
        padding: 0 0.6rem !important;
        border: 1px solid var(--ms-field-line) !important;
        border-radius: 4px !important;
        font-size: 0.82rem;
        line-height: 30px;
        color: #1f2937;
        background-color: #ffffff !important;
        box-shadow: none !important;
        transition: border-color 0.15s, box-shadow 0.15s;
    }

    /* The operations `background: #fff !important` shorthand wipes Bootstrap's
       chevron, so a combobox would read as a text box. Put it back. */
    .operation-form .ms-table .form-select {
        padding-right: 1.9rem !important;
        background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%237c2d12' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") !important;
        background-repeat: no-repeat !important;
        background-position: right 0.6rem center !important;
        background-size: 11px 11px !important;
    }

    .operation-form .ms-table .form-control:focus,
    .operation-form .ms-table .form-select:focus {
        border-color: var(--ms-primary) !important;
        box-shadow: 0 0 0 2px rgba(238, 143, 69, 0.2) !important;
    }

    .operation-form .ms-table .form-control[readonly] {
        background-color: #faf6f2 !important;
        color: var(--ms-muted);
    }

    /* Amount: "$" before a fixed amount, "%" after a percent, nothing for remaining. */
    .ms-amount {
        position: relative;
    }

    .ms-unit {
        position: absolute;
        top: 0;
        line-height: 32px;
        font-size: 0.78rem;
        font-weight: 600;
        color: var(--ms-muted);
        pointer-events: none;
        display: none;
    }

    .ms-unit-prefix { left: 0.6rem; }
    .ms-unit-suffix { right: 0.6rem; }

    .ms-amount.is-fixed .ms-unit-prefix,
    .ms-amount.is-percent .ms-unit-suffix {
        display: block;
    }

    .operation-form .ms-table .ms-amount.is-fixed .form-control { padding-left: 1.35rem !important; }
    .operation-form .ms-table .ms-amount.is-percent .form-control { padding-right: 1.5rem !important; }

    .ms-amount .form-control::-webkit-outer-spin-button,
    .ms-amount .form-control::-webkit-inner-spin-button {
        -webkit-appearance: none;
        margin: 0;
    }

    .ms-amount .form-control {
        -moz-appearance: textfield;
    }

    .ms-error {
        margin-top: 0.25rem;
        color: #b91c1c;
        font-size: 0.72rem;
        line-height: 1.25;
    }

    /* Status switch. `.ms-card` out-ranks `.operation-form label`. */
    .ms-card .ms-toggle {
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        height: 32px;
        margin: 0;
        cursor: pointer;
        user-select: none;
        font-size: 0.78rem;
        font-weight: 600;
        color: #374151;
    }

    .ms-toggle input {
        position: absolute;
        opacity: 0;
        width: 1px;
        height: 1px;
    }

    .ms-toggle-track {
        position: relative;
        width: 34px;
        height: 18px;
        border-radius: 999px;
        background: #d6cdc5;
        transition: background-color 0.15s;
        flex: 0 0 auto;
    }

    .ms-toggle-thumb {
        position: absolute;
        top: 2px;
        left: 2px;
        width: 14px;
        height: 14px;
        border-radius: 50%;
        background: #ffffff;
        box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2);
        transition: transform 0.15s;
    }

    .ms-toggle input:checked + .ms-toggle-track {
        background: var(--ms-primary);
    }

    .ms-toggle input:checked + .ms-toggle-track .ms-toggle-thumb {
        transform: translateX(16px);
    }

    .ms-toggle input:focus-visible + .ms-toggle-track {
        box-shadow: 0 0 0 2px rgba(238, 143, 69, 0.35);
    }

    .milestone-row.is-inactive td:not(.ms-col-status):not(.ms-col-action) {
        opacity: 0.55;
    }

    .ms-icon-btn {
        width: 32px;
        height: 32px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid var(--ms-field-line);
        border-radius: 4px;
        background: #ffffff;
        color: var(--ms-muted);
        transition: color 0.15s, border-color 0.15s, background-color 0.15s;
    }

    .ms-icon-btn:hover,
    .ms-icon-btn:focus-visible {
        color: #b91c1c;
        border-color: #fca5a5;
        background: #fef2f2;
        outline: none;
    }

    .ms-empty {
        padding: 1.5rem 1rem;
        text-align: center;
        color: var(--ms-muted);
        font-size: 0.82rem;
    }

    .ms-card-footer {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1rem;
        border-top: 1px solid var(--ms-line);
        background: #fffcf9;
        font-size: 0.76rem;
        color: var(--ms-muted);
    }

    .ms-card-footer:empty {
        display: none;
    }

    .ms-total {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.2rem 0.6rem;
        border: 1px solid var(--ms-line);
        border-radius: 999px;
        background: #ffffff;
        color: #374151;
    }

    .ms-total strong {
        color: var(--ms-ink);
    }

    .ms-total.is-over {
        border-color: #fca5a5;
        background: #fef2f2;
        color: #b91c1c;
    }

    .ms-total.is-over strong {
        color: #b91c1c;
    }
</style>
