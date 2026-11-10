# Transaction Portal

Finance → Transaction Portal (`/transaction-portal`). Money coming in and going
out, one week (Sun–Sat) or one month at a time. The design is the approved
prototype; keep the screen as it is.

## Sections

| Key | Section | Rows | Placed in the week by |
|-----|---------|------|-----------------------|
| `ear` | Expected Accounts Receivable | `account_transactions`, `status = Open`, `amount > 0` | `transaction_date` |
| `aar` | Actual Accounts Receivable | one item per payment received on them (`transaction_payments`), part payments too; a row closed before payments were recorded one by one shows its one settlement | `paid_on` (`settled_date` for the old rows) |
| `com` | Commission Payable | `payee = sales_partner`, `amount < 0` | `due_date` (falls back to `transaction_date`) |
| `lab` | Labor Payable | `payee = sub_contractor`, `amount < 0` | `transaction_date` (the Inspection Approval Date) |
| `mat` | Material Invoices Payable | `project_invoice_details`, `invoice_type = material` | invoice date + `material_due_days` (30) |
| `oth` | Other Payables | `amount < 0` with any other payee (Supplier, Others, Customer, a user-added payee) or none | `due_date` (falls back to `transaction_date`) |

**Other Payables** (added 2026-11-10) behaves exactly like Commission / Labor:
same permission (`Transaction Portal Other Sections`), Mark as paid, Reopen,
batch, "Earlier, still open", past-week rule, and it counts in the Payables
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

## Batch (was "Pay run")

Finance ticks the open payables to pay on a day (Commission, Labor, Material,
Other, system-raised or hand-entered alike) and clicks **Add to batch**; the day
defaults to the next Friday. It is stored as `pay_run_date` on
`account_transactions` / `project_invoice_details` (route
`transaction-portal.pay-run`, same per-row permission check as closing). The
column and route keep the old name; only the screen says Batch.

- **A batch hides and moves nothing** (since 2026-11-11). Every lane shows what
  it always shows; the picked rows only gain a "Batch 11/13" chip. Before, a
  week with a pay run showed only the picked payables and pushed every other
  open payable - in every lane, not just the one picked from - to next week;
  that rule (`PAY_RUN` mode, `hasPayRun()`) is gone.
- The **Batch** filter (Filter bar) narrows the payable lanes (Commission,
  Labor, Material, Other) to the rows batched for a day of the period shown.
  Expected AR and Actual AR are not filtered by it (the client asked for that).
- An open payable batched for a day of the period is loaded with the period
  even if it falls due later, so the filter finds it.
- A batch that is not paid by the end of its week carries on as open; its old
  date no longer counts in later weeks (re-pick it).
- Expected AR has no batch (422). The day cannot be in the past (422).
- Also: a **Batch** tile (to pay / paid), a Batch box in the drawer, and a
  `Batch` column in the CSV (same place as the old `Pay Run`).

## Search (every date)

The search box at the top **always searches every date** (since 2026-11-10;
before that it filtered only the period on screen unless an "All time" box was
ticked - the box is gone). From 2 characters the search goes to the server
(`transaction-portal.search`, `TransactionPortalService::search()`, debounced
350 ms): every row of any date and status, in the sections the user may see,
scoped the same way as the board (`filterByRole`, live projects only), and each
result is shown in its own lane. One character still filters the period on
screen; clearing the box goes back to it. The per-lane search boxes (and the
table's) stay local filters on what is loaded.

- Matches the project name and code, the customer's first / last name, sales
  partner, sub-contractor, finance option, milestone, transaction details,
  payee (by its label, so user-added payees are found), the labor/material
  invoice file name, and - when the search is a number (`$4,560` works) - the
  amount or the amount received / paid.
- At least 2 characters (422 otherwise). Newest first, at most
  `SEARCH_LIMIT` (200) rows; the tile says when there were more.
- Results show per section with no week split, and open / pay / reopen / pay
  run work on them as usual. Export is off while it is on. Choosing a week,
  month, Previous / Next or "Go to today" clears the search and goes back to
  that period.
- Positive transactions closed before settlement existed (no received date) are
  not found, as they are never in Actual AR.

The Expected AR amount is the remitted amount (`amount - deduction_amount`),
less whatever part payments have already come in (see Part payments).

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
closes each ticked payable at its own amount on one date. Expected AR rows take
a tick box too (added 2026-11-10): ticking payments turns
the bar into "Received on [date] · Mark as received", which records each at its
full expected amount (amount − deduction) on that date - so each moves to Actual
AR and raises the commission exactly as one recorded from the drawer would. A
partial amount still goes through the drawer. Payments and payables can be
ticked together (the bar counts them apart, says "received / paid"); the pay
run controls appear only for payables and send only payable ids.

**Deleting received payments (duplicates).** Actual AR rows take a tick box
(and the drawer a Delete button) only for a user with the Actual AR section
AND `Account Transactions Edit` (`PERMISSION_DELETE`; page config `canDelete`).
`POST transaction-portal/delete-received` checks every row before deleting any
(each must resolve to `aar` - a closed positive row - under the user's own
section permission), then soft-deletes it with an activity-log line, so it can be
restored. To help spot duplicates, each Actual AR row carries hints
(`withReceivedHints()`): **Imported** (details start "Imported from"), **Same
amount** (another live positive payment on the project has the same amount -
the 2026-11-09 import doubled hand-entered rows under other milestone names,
e.g. "Permit" vs "Permit Approval Date", "Storage Milestone 2" vs "Storage M2"),
and `commissionAfter` (a sales-partner commission dated on/after the payment was
received). The confirm dialog lists the rows and, for the last case, warns that
deleting the payment does **not** take the commission back. The project
page's Account Transactions form asks for the same two fields when a row is switched
to Closed. A paid payable can be reopened, which clears both fields.

**Editing a closed row on the project page** saves its Received / Paid amount
and date as they are in the form (`AccountTransactions::editedSettlement()`).
Until 2026-11-10 an edit to a row that was already Closed kept the old ones, so
the portal - which shows the settled amount and places the row by the settled
date - kept the old figure however often the row was re-saved; the only way
round was Open, save, Closed, save. A row closed at its full amount follows a
new amount when the received box is left as it was; a part payment keeps its
received amount. Blank boxes (rows closed before settlement existed) are kept
blank.

## Adding from the portal ("+ New", added 2026-11-11)

A **+ New** button in the header and on each lane opens a form in the drawer
(the same panel as Record payment) with a Section picker and only the fields
that section needs. `POST transaction-portal/store`
(`TransactionPortalController::store` → `TransactionPortalService::create`);
the project dropdown (a plain select, A-Z, loaded once) is `GET transaction-portal/projects` (the import's
`pickableProjects()`, so the same project scoping), and the server checks
`ProjectService::canAccessProject` again.

| Section | Creates | Asks for |
|---|---|---|
| Expected AR | positive transaction, Open | project, payee, milestone, amount, deduction (optional), expected date, details |
| Actual AR | the same, then received through the payments ledger (so a smaller amount is a part payment and the commission is worked out) | + amount received, date received |
| Commission | `payee = sales_partner`, negative, Open | project, milestone (Commission), amount, payment received date, due date (optional: the Friday of the week after), details |
| Labor | `payee = sub_contractor`, negative, Open | project, milestone (Full Payment), amount, inspection approval date, details |
| Other | negative, Open, any payee but those two | project, payee, milestone, amount, date, due date (optional), details |
| Material | `project_invoice_details` material invoice, with its file; updates the project's `actual_material_cost` (`ProjectInvoiceDetail::syncProjectCost`, shared with the Invoices tab) | project, invoice amount, invoice date, file, notes (optional) |

**Who:** the section's own permission AND what adding takes on the project
page: `Account Transactions Edit` for a transaction, `Invoice Details` for a
material invoice (`creatableSections()`; page config `creatable`). The form
checks every required field before sending and lists what is missing; the
server's 422 messages show in the same box. Each add is in the activity log
("created the account transaction from the Transaction Portal").

## Miscellaneous payments - no project (added 2026-11-11)

Money the company receives that belongs to no project (a lender's bonus, a
program rebate, a deposit) is an account transaction with `project_id` NULL
(migration `2026_11_11_000001`; the foreign key stays).

- Only **money in**: Expected AR / Actual AR. The "+ New" project dropdown
  offers **No project (Miscellaneous payment)** (`project_id=misc`) for those
  two sections only; any other section still needs a project (422).
- On the board its project is **Miscellaneous payment** (`MISC_LABEL`, row flag
  `misc`), and its meta line shows the milestone (e.g. "GoodLeap Bonus") where
  a project payment shows the finance option. No "Open project" link.
- It is received / part paid like any payment and moves to Actual AR, but
  raises **no commission** (no project, so no sales partner), and gets no
  "Same amount" / commission hints.
- Visibility: `transactions()` takes "a live project, or none"
  (`onLiveProjectOrNone`). Sales Person / Sub-Contractor Manager reach rows
  through the project, so they never see these.
- Search finds them by milestone / details / payee / amount, or by typing
  "misc".
- **Import:** the preview's project select has the same option; such a row
  must be money in (a negative amount is skipped with the reason).
- The Transaction Report and its export show "Miscellaneous payment" for them
  (the report table used to read the project's name straight off and would
  have failed on such a row).

## Part payments (added 2026-11-10)

Every section takes a part payment: Expected AR and all four payables. Each
payment is a row in `transaction_payments` (`account_transaction_id` or
`project_invoice_detail_id`, `amount`, `paid_on`, `created_by`, soft deletes),
written only through `TransactionPaymentService`, which keeps the parent in step:

- `settled_amount` = the total received / paid **so far** (also on an Open row),
  `settled_date` = the day of the last payment.
- **Open** while the total is below the amount (positive rows: amount −
  deduction; payables: |amount|), **Closed** once it reaches it - or when the
  payment was ticked "This is the last payment" (`final`), which closes it short.
- An open row's board amount is its **balance**; its chip says "Part paid $X of
  $Y". The drawer shows the steps: each payment (date, who, running total) and
  the balance still to come, each with **Remove**
  (`transaction-portal.remove-payment`, the permission of the section the row is
  in; the row reopens with its balance). **Reopen** removes every payment.
- **Actual AR lists payments, not transactions**: item id `p<payment id>`
  (`transactionId` names its row), placed by `paid_on`, so a week's Received
  total is the money that came in that week. A part payment carries `partOpen`
  and a "Part · $X to come" chip. Rows closed before this existed have no
  payment rows and still show from `settled_amount` / `settled_date` (id
  `t<id>`); nothing was backfilled. Deleting a received payment that is one of
  several on its row removes only that payment; a row's only payment deletes the
  row, as before.
- The bulk bar receives / pays each ticked row's **balance**.
- Payables keep one item per row: a part payment made in a week that is now past
  is not listed under that week (the row sits in the current week, part paid).
- The project page's form: the Closed box is the **total** received / paid.
  Closing an open, part-paid row records only what the earlier payments did not
  cover; editing a closed row's total moves its last payment (refused below the
  earlier payments); setting a Closed row back to Open removes its payments, as
  Reopen does; editing an Open row keeps its part payments.
- CSV: `Full Amount` and `Paid So Far` columns, last.

Rows closed before these columns existed have no settlement, so they never
appear in Actual AR. Every material invoice on file at the migration was set
to Closed, the way the account transactions status migration closed every
existing row. Only invoices uploaded from then on show as payable.

## Commission (`CommissionPayableService`)

Raised when a customer payment is received - in full or in part - from the
portal or the Account Transactions form, worked out over the whole project:

```
commission now = all money received (settled_amount of positive rows, part payments included)
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
