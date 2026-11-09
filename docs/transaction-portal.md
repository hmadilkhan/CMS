# Transaction Portal

Finance → Transaction Portal (`/transaction-portal`). Money coming in and going
out, one week (Sun–Sat) or one month at a time. The design is the approved
prototype; keep the screen as it is.

## Sections

| Key | Section | Rows | Placed in the week by |
|-----|---------|------|-----------------------|
| `ear` | Expected Accounts Receivable | `account_transactions`, `status = Open`, `amount > 0` | `transaction_date` |
| `aar` | Actual Accounts Receivable | the same rows once closed | `settled_date` |
| `com` | Commission Payable | `payee = sales_partner`, `amount < 0` | `due_date` (falls back to `transaction_date`) |
| `lab` | Labor Payable | `payee = sub_contractor`, `amount < 0` | `transaction_date` (the Inspection Approval Date) |
| `mat` | Material Invoices Payable | `project_invoice_details`, `invoice_type = material` | invoice date + `material_due_days` (30) |

Each section shows the rows that fall in the period **plus every earlier row
that is still Open** ("Earlier, still open", in red), so nothing overdue drops
out of sight. Expected AR shows every open money-in row dated up to the end of
the period. A payment leaves Expected AR the moment it is closed and appears in
Actual AR under the week it was received.

**A past week or month is closed.** Once a period's last day is before today,
its Commission, Labor and Material sections show only the rows **paid** in it.
An unpaid payable is no longer listed under the old week where it fell due, or
under every week since; it shows in the current week under "Earlier, still
open" (and in future weeks). Expected AR and Actual AR are unchanged by this.
The rule lives in `TransactionPortalService::rows()` (`isClosedPeriod()`), so
the board, the table and the CSV export all follow it.

## Pay run (pick on Thursday, pay on Friday)

Finance ticks the open payables to pay this week (Commission, Labor, Material,
system-raised or hand-entered alike) and clicks **Add to pay run**; the day
defaults to the next Friday. It is stored as `pay_run_date` on
`account_transactions` / `project_invoice_details` (route
`transaction-portal.pay-run`, same per-row permission check as closing).

- **A week with a pay run** (any payable with `pay_run_date` in it) shows, of
  its open payables, **only the picked ones**. Every other open payable moves to
  the next week, where it sits under "Earlier, still open". Nothing is
  rewritten: due dates, and so the overdue days, stay as they were. Paid rows
  show as always. Taking the last row out of the pay run restores the week.
- **A month is never narrowed** by a pay run (`isWeek()`), and a past week is
  already paid-only.
- A pay run that is not paid by the end of its week just carries on as open in
  the next week; its old `pay_run_date` no longer counts there (re-pick it).
- Expected AR has no pay run (422). The day cannot be in the past (422).
- The page shows a **Pay run** filter (the Friday list), a **Pay run** tile
  (to pay / paid), a chip on each picked row, a Pay run box in the drawer, and a
  Pay Run column in the CSV.

The Expected AR amount is the remitted amount (`amount - deduction_amount`).

## Permissions

- `View Transaction Portal`: the page.
- `Transaction Portal Expected AR`: the Expected AR section (Funding Manager).
- `Transaction Portal Other Sections`: Actual AR, Commission, Labor, Material.

A section's permission also lets the user close its rows. The controller checks
it **per row** (`TransactionPortalController::resolve`), so a Funding Manager
cannot close a payable by posting its id. The migration
`2026_11_04_000004` gives the Funding Manager the first two and Admin all three.

## Closing a row

Closing always records **what** was received / paid and **when**:
`settled_amount` + `settled_date` on `account_transactions` and on
`project_invoice_details`. The portal's drawer asks for both. The bulk bar
closes each ticked payable at its own amount on one date. The project page's
Account Transactions form asks for the same two fields when a row is switched
to Closed. A paid payable can be reopened, which clears both fields.

Rows closed before these columns existed have no settlement, so they never
appear in Actual AR. Every material invoice on file at the migration was set
to Closed, the way the account transactions status migration closed every
existing row. Only invoices uploaded from then on show as payable.

## Commission (`CommissionPayableService`)

Raised when a customer payment is closed (from the portal or the Account
Transactions form), worked out over the whole project:

```
commission now = all money received (settled_amount of closed positive rows)
               - Labor invoices - Material invoices
               - commission already raised (every negative sales_partner row)
```

- Nothing is raised until **both** a Labor and a Material invoice are on file,
  or while the result is not above 0. The next payment received works it out
  again. Uploading an invoice later does not trigger it by itself.
- Payee Sales Partner, amount negative, `transaction_date` = date received,
  `due_date` = **Friday of the week after** that date (`dueDate()`, weeks start
  on `transaction_portal.week_starts_on`).
- Hand-entered commission rows count as already raised, so it is never paid twice.

## Labor

The Labor Payable is still raised by `SubContractorPayableService` on the
Inspection Approval Date. It has **no due date** yet (client to confirm), so it
never shows as overdue.

Covered by `tests/Feature/TransactionPortalTest.php`.
