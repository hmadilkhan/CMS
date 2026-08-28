# Paperwork Follow Ups

Reference for the three "chases" the CRM runs, and for the closable-lane
mechanism they depend on. Read this before changing any of it.

---

## 1. What a chase is

A chase watches one question on a project. While the answer says a document is
still owed, the project sits on the owning department's dashboard, and **one
specific move in the pipeline is intercepted**: instead of the lane the user
picked, the project lands in that chase's *parked lane*. Parked lanes are closed
to manual movement, so the project waits there until the document is produced —
which clears the chase, releases the project to the next lane, and e-mails the
assignee.

All three are the same code. Only a config row differs.

| | **MPU** | **Utility Bill** | **Fire Review** |
|---|---|---|---|
| Type key | `mpu` | `utility_bill` | `fire_review` |
| Owner department | Engineering (3) | Deal Review (1) | Permitting (4) |
| Opens when | `mpu_required` = `yes` | `utility_bill_required` = `no` | `fire_review_required` = `1` |
| …and | no `meter_spot_result` | no `utility_bill` file | no `fire_review` file |
| Intercepted move | Permitting → Installation | Inspection → PTO | Installation → Inspection |
| Parked lane | 31 Install Pending Document | 32 PTO Pending Document | 29 Inspection Pending Fire Review |
| Release lane | 12 Install Not Scheduled | 18 PTO | 16 Inspection Not Scheduled |
| Cleared by | picking the Meter Spot Result | uploading the bill | uploading the approval |
| Card collects | a **value** (dropdown) | a **file** | a **file** |
| Files section | — | "Utility Bills", Deal Review tab | "Fire Approval Documents", Inspection tab |

The utility bill question is the odd one out: its field is labelled **Utility
Bill Uploaded**, so `no` — not `yes` — is the answer that owes a document.
`NULL` (unanswered) chases nothing; the field is a required Deal Review field, so
it cannot stay `NULL` past Deal Review.

Sub-department ids above are fixed records in `sub_departments`. If they are ever
renumbered, `DocumentFollowUpService::TYPES` must be updated to match.

### The NTP approval gate shares the MPU move

**Permitting → Installation is also gated on `projects.ntp_approval_date`.** It
is not a chase — nothing is parked and nothing is tracked — it simply refuses the
move while the date is missing and lets the move modal supply one:

- `ProjectController::ntpApprovalGate()` returns `422` with
  `requires: ntp_approval_date`; the modal reads that flag, reveals a date input
  and re-sends the same move with the date filled. The date is then written
  inside the move's own transaction.
- The modal also reveals the input **up front** for that move when the project
  has no date, so the user is not made to click into a rejection first. The
  server stays the real gate.

**This move modal is now the only place Operations collects the date.** The NTP
Approval Date used to be a Deal Review department field; it was removed from
both the edit and view panels and its `project_department_fields` row was
deleted (migration
`2026_08_28_000007_remove_ntp_approval_date_from_deal_review_fields`), so a
project can leave Deal Review without it and is asked for it here instead. The
funding side can also see and fill the date ahead of time in the Zones **NTP**
tab (`docs/zones.md` §7) — that tab records it, it never gates anything.

**Order matters: the NTP gate runs BEFORE `forcedTypeForMove()`.** A project that
owes both the NTP date and an MPU meter spot result is asked for the date first;
only once it is supplied does the move run and the MPU chase park the project in
lane 31. Never move the gate below the interception — the project would land in
the parked lane with no NTP date, and the parked lane is closed to manual moves.

The gate resolves its two departments **by name** (`Permitting`, `Installation`),
like `TYPES` does, so renumbering department ids does not break it.

---

## 2. Where the code lives

| Concern | File |
|---|---|
| All the logic | `app/Services/DocumentFollowUpService.php` |
| Chase records | `app/Models/ProjectDocumentFollowUp.php` |
| File grouping | `app/Models/ProjectFile.php` (`CATEGORY_*`, `category()` / `ungrouped()` scopes) |
| Dashboard endpoint | `app/Http/Controllers/DocumentFollowUpController.php` (`POST document-followup/update`) |
| Dashboard lists | `app/Http/Controllers/HomeController.php` |
| Dashboard cards + JS | `resources/views/dashboard.blade.php` |
| Move interception | `ProjectController::moveProject()` (live) and `projectMove()` (route is commented out) |
| Department fields | `app/Livewire/Project/ProjectFields/EditFields.php` + its blade |
| Files sections | `app/Livewire/Project/EnhancedFilesSection.php` + `resources/views/projects/show.blade.php` |

### Tables

`project_document_follow_ups`
: one row per chase per project. `type`, `status` (Pending/Resolved),
  `employee_id` (owning department assignee at open time), `opened_at`,
  `resolved_at`, `resolved_reason`, `resolved_by`.

`project_files.category`
: `NULL` = ordinary department file. `utility_bill` / `fire_review` = belongs to
  that chase's own section, and is hidden from the department file list.

`sub_departments.show_in_move_list`
: `1` = normal lane. `0` = **closed lane**: never offered as a move destination
  anywhere, and a project sitting in one cannot be moved by hand. Managed from
  Operations › Sub Departments (checkbox + Shown/Hidden badge).

Project columns: `mpu_required`, `meter_spot_result`, `utility_bill_required`,
`fire_review_required`.

---

## 3. The flow, end to end

```
department field answered so a document is owed
  ("yes" for MPU and fire review, "no" for Utility Bill Uploaded)
  → DocumentFollowUpService::sync()          opens the chase
  → dashboard card appears for the owning department's assignees
  → project moves through the pipeline normally (e-mails unaffected)
  → the intercepted move happens
      → forcedTypeForMove() returns the type
      → sub-department forced to the parked lane
      → that move alone sends NO assignment e-mail
      → logForcedParkedLane() writes the note
  → project is stuck: the parked lane is closed to manual moves
  → assignee produces the document on the dashboard card
      → DocumentFollowUpController::update()
      → syncType() → resolve() → releaseFromParkedLane()
      → project moves to the release lane, assignee IS e-mailed
```

`sync()` is called after every project write that can change an answer:
`EditFields::updateProjectFields()`, `ProjectController::projectMove()`, and the
dashboard endpoint. `syncAll()` also runs on every `pendingList()` call, so a
chase opened through a path nobody hooked still shows up on the next dashboard
load.

---

## 4. Decisions that were made deliberately

Do not "fix" these without asking — each one was chosen on purpose.

**Assignment e-mail is suppressed on the intercepted move only**, not on every
move while a chase is open. The utility bill chase spans Deal Review to PTO; the
old per-project rule would have silenced the entire pipeline. `sendEmail` is a
separate flag on `ProjectAssignmentService::notifyAssignedEmployee()` so the
in-app notification still fires.

**Closing a chase always releases a parked project**, whatever the reason —
document received, question answered "no", project archived. The parked lane is
closed to manual moves, so leaving the project there would strand it forever.

**Utility bill and fire review are closed by the file, not by the dropdown.**
Answering the department field the owing way opens the chase; only uploading the
document closes it. This is why the project page's dropdown cannot release a
parked project on its own — except by retracting the answer itself (Utility Bill
Uploaded back to "yes", Fire Review Required back to "No"), which closes the
chase as "no longer required" and releases the project like any other close.

**MPU is closed by the value wherever it is entered** (project page or card),
because the meter spot result *is* the document.

**Fire review does not back-fill.** 83 projects already answered "yes" before the
chase existed; a migration marked each with a `pre_existing` resolved row and
`predatesTheChase()` keeps them off the list. **Delete a project's `pre_existing`
row to opt it back in.**

**`fire_review_required` is nullable.** It used to be `NOT NULL DEFAULT 0`, so
"not answered" and "no fire review needed" were the same value — which makes a
Permitting requirement impossible to express. Existing 0/1 answers were left
untouched; only new projects start NULL.

**Utility Bill Uploaded is phrased as the answer, not the requirement.** The
other two ask whether paperwork is *needed*; this one asks whether the bill is
already *in*, so the chase opens on "no". `paperworkRequired()` carries the only
place that difference lives — everything downstream is unchanged.

**Required department fields**: `utility_bill_required` (Deal Review) and
`fire_review_required` (Permitting) are rows in `project_department_fields`, so a
project cannot leave those departments unanswered.

---

## 5. Gotchas

**`empty()` and the value `0`.** `moveProject()`'s required-field loop calls
`empty($project->$field)`, which is true for `0`. `fire_review_required = 0`
("No") is a real answer, so it has its own branch that only checks for `NULL`,
and it is listed in the exclusion array of the generic branch. Any future
numeric-or-boolean required field needs the same treatment.

**`resolveSubDepartmentForDepartment()` falls back to the lowest-order lane.**
Parked lanes often sit at order 0, so the fallback explicitly skips closed lanes
(`selectableForMove()`). Without that, a move whose selected lane did not match
the target department would silently land in a parked lane.

**`projectMove()` is dead code.** Its route in `routes/web.php` is commented out;
every live move goes through `moveProject()`. It is kept in step anyway, but only
`moveProject()` matters for testing. The NTP approval gate was added to
`moveProject()` only, for that reason.

**The required-field check still runs first.** `moveProject()` validates the
current department's `project_department_fields` before the NTP gate, so on
production a Permitting → Installation move reports missing permitting dates
before it ever asks for the NTP date. Only the NTP-vs-MPU order was specified;
this one is unchanged.

**Two files sections come from one component.** `EnhancedFilesSection` renders
both the department file list and the category sections. `$category` NULL means
"this department's ungrouped files"; a category name means "this group across the
whole project". `allowUpload => false` hides the upload button on the category
sections, because those documents are collected from the dashboard card.

**Blade caching.** `php artisan view:cache` does not lint the compiled PHP. To
really validate a Blade change: compile, then `php -l` the files in
`storage/framework/views/*.php`.

---

## 6. Adding a fourth chase

1. Add the trigger column to `projects` (nullable — no back-fill), and register
   it in `project_department_fields` if it must be answered before leaving its
   department.
2. Create the parked lane in `sub_departments` with `show_in_move_list = 0`.
3. Add a `TYPE_*` constant and a `TYPES` entry in `DocumentFollowUpService`:
   owner / from / to department names, parked and release sub-department ids,
   and either `file_category` (collects a document) or `value_column` +
   `value_options` (collects a value).
4. Extend `paperworkRequired()` with the new column's test. Everything else —
   opening, closing, interception, release, e-mail, logging — is already generic.
5. If it collects a file: add a `CATEGORY_*` constant to `ProjectFile`, and
   render an `EnhancedFilesSection` instance for it under the relevant department
   tab in `show.blade.php`.
6. Add the dashboard card in `dashboard.blade.php` (copy an existing one; the JS
   is shared and keys off `data-follow-up-type`), and the two variables in
   `HomeController::dashboard()`.
7. If existing projects already answered "yes" and should not be chased, write a
   migration that inserts `pre_existing` resolved rows for them.
8. Register the new column in `config/ai_schema.php` and
   `config/ai_field_dictionary.php`, then run `php artisan ai:schema-audit`.

---

## 7. Logging

Every step writes to both places the CRM keeps history:

- `activity_log` — events `document_follow_up_opened`, `_cleared`,
  `_lane_forced`, `_lane_released`, with a `follow_up_type` property.
- `department_notes` — the same message prefixed with the chase's label, e.g.
  `[Utility Bill Follow Up] …`.

`resolved_at` on the chase row is the authoritative "when it left the list".

---

## 8. Deploying

`deploy.sh` only runs `git pull`. After any push that touches this feature:

```bash
php artisan migrate --force
php artisan config:clear
php artisan cache:clear
php artisan view:clear
```

`config:clear` matters because the AI schema and field dictionary are config
files. `npm run build` is not needed — the dashboard JS is inline in the Blade.
