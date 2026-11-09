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
| `oth` | Other Payables | `amount < 0` with any other payee (Supplier, Others, Customer, a user-added payee) or none | `due_date` (falls back to `transaction_date`) |

**Other Payables** (added 2026-11-10) behaves exactly like Commission / Labor:
same permission (`Transaction Portal Other Sections`), Mark as paid, Reopen,
pay run, "Earlier, still open", past-week rule, and it counts in the Payables
due tile and the Net position. Payees with their own section are
`TransactionPortalService::OWN_SECTION_PAYEES`; the money-out sections are
`PAYABLE_SECTIONS` (the controller's pay-run / reopen checks and the page's
`PAYABLES` list read it - keep them in step). Before this, those rows were saved
but never shown in the portal. The CSV export has a `Payee` column, last, so the
existing columns keep their place.

**Layout.** Lanes are `minmax(236px, 1fr)`, so six fit a ~1,510px window
(1920 at 125% scaling is 1,536). Below that only the board scrolls sideways,
inside `.board-scroll`: `.tp` is a grid with `grid-template-columns:
minmax(0, 1fr)` and `.tp > * { min-width: 0 }`. Without those, its implicit
`auto` column grew to the board's minimum width and the WHOLE page scrolled,
cutting off the title and header (reported 2026-11-10 at ~1,550px). A card's
`.main` is `minmax(0, 1fr)` too, so a long project name ends in "…" instead of
pushing the card out of its lane. Checked in headless Edge at 1366 / 1550 /
1920.

Each section shows the rows that fall in the period **plus every earlier row
that is still Open** ("Earlier, still open", in red), so nothing overdue drops
out of sight. Expected AR shows every open money-in row dated up to the end of
the period. A payment leaves Expected AR the moment it is closed and appears in
Actual AR under the week it was received.

**A past week or month is closed.** Once a period's last day is before today,
its Commission, Labor, Material and Other sections show only the rows **paid** in it.
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

## Search ("All time")

The search box normally filters only the rows loaded for the period on screen
(in the browser). Ticking **All time** sends the search to the server instead
(`transaction-portal.search`, `TransactionPortalService::search()`): every
row of any date and status, in the sections the user may see, scoped the same
way as the board (`filterByRole`, live projects only).

- Matches the project name and code, the customer's first / last name, sales
  partner, sub-contractor, finance option, milestone, transaction details,
  payee (by its label, so user-added payees are found), the labor/material
  invoice file name, and - when the search is a number (`$4,560` works) - the
  amount or the amount received / paid.
- At least 2 characters (422 otherwise). Newest first, at most
  `SEARCH_LIMIT` (200) rows; the tile says when there were more.
- Results show per section with no week split, and open / pay / reopen / pay
  run work on them as usual. Export is off while it is on. Choosing a week,
  month, Previous / Next or "Go to today" leaves the all-time search.
- Positive transactions closed before settlement existed (no received date) are
  not found, as they are never in Actual AR.

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

## Import (account transactions from a spreadsheet)

The **Import** button (shown with `Account Transactions Edit`, which the routes
also require) opens `/transaction-portal/import`. Files:
`TransactionPortalImportController`, `TransactionImportService`,
`resources/views/transaction-portal/import.blade.php`.

1. **Upload** an .xlsx / .xls / .csv (≤ 1000 rows) with a date and a status for
   rows that have none. Without a header row the columns are read as
   Project Name, Payee, Milestone, Amount, Date, Status, Details (the client's
   sheet has the first four only). With a header row they are found by name, in
   any order, and `Project Code` may replace the name. Formulas are read as
   their result; `$ 9,072.00` and `(1,000)` are understood.
2. **Preview** - nothing is saved. Every row is an editable line: project,
   payee (free text with the payee list), milestone, amount, date, status,
   details, plus an Import tick. The parsed rows wait in the cache (2 h) under
   a token tied to the user.
   - **Project match**: names are compared on letters and digits only, against
     the project name, then the customer's first+last name, then as the start of
     a project name (sibling projects -> "more than one matches"). A name ending
     `- Solar` / `- Storage` / `- Battery` is matched without that word, and the
     word is put at the **start** of the milestone: `Jun Du - Storage` +
     `Milestone 1` -> `Storage Milestone 1` (the client's wording, 2026-11-10).
   - **No match**: a project select with the **closest names first**
     (`similar()`: spelling similarity + whole words of the name, so reversed or
     misspelt names - "Spencer Fabian" -> Fabian-Spencer - come up top), then
     every project. The 3 non-project rows of the client's first sheet (Charge
     Ready Home Program, Taamiat Deposit, GoodLeap) have no close name.
   - **Duplicates** (same project + payee + milestone + amount, already in the
     system or earlier in the file) open **unticked**; "Import them anyway"
     ticks them. The date is not part of the key - the client's sheet has none.
     The milestone is compared by its words in any order (`milestoneKey()`), so
     `Storage Milestone 2` matches an older row keyed in as `Milestone 2 - Storage`.
   - A payee that does not exist is created at import (`findOrCreateByLabel`)
     and listed as "New payee" first.
   - The page checks ticked rows before posting (project and values filled,
     amount a non-zero number) and marks what is missing.
3. **Import** writes the ticked rows with the values as left. Each is re-checked
   on the server (incl. `ProjectService::canAccessProject`, so a posted project
   id the user cannot see is refused); anything still incomplete is skipped and
   listed with its reason. A Closed row gets `settled_amount` = |amount −
   deduction| and `settled_date` = its date. **Only what the sheet holds is
   written**: no commission, finance milestone or Labor Payable is raised (the
   client asked for this), and each row is logged in the activity log with the
   file name and row number.

Covered by `tests/Feature/TransactionPortalImportTest.php`.
