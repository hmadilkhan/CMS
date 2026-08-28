# Zones

Reference for the Zones module — the funding-side pipeline built for the
**Funding Manager** role. Read this before changing any of it.

---

## 1. What a zone is

A zone says where the *funding* side has a project. It runs **beside** the
department pipeline and never writes to it: a project can be in the Permitting
department and the M1 zone at the same time, and neither one moves the other
(with the two exceptions in §3).

Five zones, seeded by migration and not managed from the UI:

| Order | Slug | Name | On the board |
|---|---|---|---|
| 1 | `pre_ntp` | Pre NTP | yes |
| 2 | `ntp` | NTP | yes |
| 3 | `m1` | M1 | yes |
| 4 | `m2` | M2 | yes |
| 5 | `archived` | Archived | **no** |

`zones.show_in_list = 0` is what keeps Archived off the board. It is still a
valid move destination, and its projects are read back through
`/zones/archived`. This mirrors how `sub_departments.show_in_move_list` marks a
closed lane on the department side — same idea, opposite direction (a zone lane
is hidden from the *board*, not from the *move list*).

A project with `zone_id = NULL` is simply not in the module. Most of the
historic backlog is exactly that, by decision — see §4.

---

## 2. Where the code lives

| Concern | File |
|---|---|
| The whole brain | `app/Services/ZoneService.php` |
| Board, move + zone-fields endpoints | `app/Http/Controllers/ZoneController.php` |
| Config (entry, promotion, zone fields, move gate, archive, role) | `config/zones.php` |
| Lanes | `zones` table, `App\Models\Zone` |
| Current zone | `projects.zone_id`, `projects.zone_entered_at` |
| History | `project_zone_movements`, `App\Models\ProjectZoneMovement` |
| Zone notes | `project_zone_notes`, `App\Models\ProjectZoneNote` |
| Zone files | `project_zone_files`, `App\Models\ProjectZoneFile` |
| Board fragment | `resources/views/zones/partials/board.blade.php` (+ `project-card`, `move-modal`) |
| Board's host page + tabs + JS | `resources/views/projects/index.blade.php`, `projects/scripts.blade.php` |
| Project page section (inside Project Activity) | `resources/views/projects/partial/zones-tab.blade.php` |
| Notes/files components | `App\Livewire\Project\NotesSection`, `EnhancedFilesSection` (zone mode) |
| Tests | `tests/Feature/ZoneWorkflowTest.php` |
| Routes | `routes/web.php`, `zones.index` (redirect) / `zones.board` / `zones.move` / `zones.fields`, gated `can:View Zones` |

---

## 3. How a project gets its zone

Exactly **two** things happen on their own. Everything else is the Funding
Manager pressing Move.

1. **Entry.** A project reaching **Deal Review** (`config('zones.entry')`) with
   no zone yet is enrolled at **Pre NTP**.
2. **Promotion.** A project reaching **Site Survey**
   (`config('zones.promotion')`) **while still in Pre NTP** is promoted to
   **NTP**.

Rule 2 is deliberately one-directional. Once the Funding Manager has moved a
project on to M1 or M2, a department move can never pull its zone backwards —
the manual decision outranks the pipeline. `handleDepartmentArrival()` is the
single entry point for both rules and is safe to call after any department
change.

One extra case lives inside the promotion branch: a project created *straight
into* Site Survey (the intake form can do that) never passed the entry rule, so
it enters at NTP rather than staying outside the module forever.

Hook points (all call `ZoneService::handleDepartmentArrival`):

- `ProjectController::store()` — new project, department 1.
- `CustomerController::store()` — project created with the customer, department 1.
- `IntakeFormController::store()` — department 1 or 2 depending on `schedule_survey`.
- `ProjectController::moveProject()` — after the department move commits.

The legacy `ProjectController::projectMove()` has **no** hook; its route is
commented out and it is dead code.

---

## 4. The backfill (why most projects have no zone)

The migration enrolled **only the projects sitting in Deal Review at the time**
(3 rows), at Pre NTP, each with an `is_auto` movement row. Everything further
down the pipeline was left alone on purpose: the Funding Manager pulls in what
they want by hand rather than inheriting a board of hundreds of projects at the
wrong stage.

Consequence to remember: **a project with no zone shows no Zones section** on its
project page, and appears on no lane.

---

## 5. The board

**The board is not a page of its own — it is the "Zones" tab of the projects
page.** `/projects` carries an `Operational | Zones` switch at top centre
(`projects/index.blade.php`); Operational is the department board that was always
there, Zones is this one. `/zones` only redirects to `/projects?tab=zones`, which
is also where the sidebar's Zones item points.

**Kanban.** `zones.board` returns a *fragment*, not a page: one column per
`show_in_list` zone side by side, that zone's project cards stacked down the
column. Columns scroll sideways when there are more than fit; cards scroll inside
their own column so the headers stay put.

Every column is the **same fixed height** (`height: 72vh` + `align-items:
stretch`), whatever it holds. Letting them size to their content made an empty
zone collapse to a bare header and the board look broken.

The department filter is a **full-width segmented tab strip**, not a dropdown —
the same look as the project page's `#departmentDetailTabs`, rebuilt inside
`board.blade.php` because those rules are scoped to that page's own wrapper.

**The card is the projects page card** — same header, avatar, days badge, info
rows, progress bar and notes strip — plus two board-only bits: "In this zone"
(from `zone_entered_at`) and a Move Zone button. It is written **null-safe**
(`zones/partials/project-card.blade.php`): a zone can hold a project with no
active task, no finance option and no sales partner, none of which the projects
page ever has to survive.

It is also **deliberately more compact** than the projects page card. At the
original size one card filled a column and the next project was hidden below the
fold with nothing to say so. Three things fixed that, and all three should be
kept: the project code sits in the header instead of costing an info row, the
notes strip is one truncated line (full text in its `title`), and the paddings /
font sizes are trimmed throughout. On top of that `.zone-column-body` styles its
scrollbar rather than leaving it as an overlay one, so a column with more cards
below actually looks scrollable.

The fragment carries its own search box, department strip and Archived toggle;
`projects/scripts.blade.php` re-fetches it on any of those, and again whenever the
move modal fires `zone:moved`. Because the whole fragment is replaced on every
fetch, the current filters are held in the page's `zoneBoard` JS object rather
than read back off the markup, and the search box is re-focused after a
search-driven re-render so typing is not interrupted. Filters are applied in
`ZoneController::projectsFor()`, so a column's count always matches its cards.
`?archived=1` swaps the open columns for the single archive column.

Moving is the **only** write the board makes: `POST /zones/move` with
`project_id`, `zone_id` and an optional `note`. Any zone is a valid destination,
forwards or backwards, the archive included. A move to the zone the project is
already in is refused with 422. **No zone move is gated on a project field** —
see §7.

---

## 6. The project page's Zones section

**Inside the "Project Activity" tab, directly under the department section** —
not a tab of its own. Rendered only when the project has been enrolled
(`$project->zone_id` is set) and the viewer has `View Zones`.

It is deliberately built from the *same pieces* as the department section above
it, so the two read as one design: the `department-detail-heading` pill, the
identical `nav nav-tabs project-department-tabs tab-body-header rounded
justify-content-center mb-4` tab bar with `01 Pre NTP`-style labels, and the same
`row clearfix sample-activity-grid` → `col-lg-8 sample-notes-column` +
`col-lg-4 sample-files-column` grid. The **Zone History** panel sits under the
notes column in a `project-section-panel` / `department-fields-frame`, exactly
where **Department Fields** sits in the department section.

Above the tab bar sits the `zone-status-bar`: one full-width bar the same width
and frame as the tab strip below it, with "Current Zone / <name> / days in zone"
on the left and the Move Zone button on the right. It is deliberately a single
bar rather than a row of loose pills - two competing gradient pills next to muted
text read as clutter.

**Every zone gets a tab** — all five, not only the ones the project has been
through. This is a deliberate difference from the department tabs (which show
only visited departments): a zone the project has not reached yet simply opens
empty. The tab the page opens on is the zone the project is in right now.

**Only the current zone's tab is editable.** Every other zone tab is read-only:
no note box, no upload button, and no edit/delete control on the notes and files
already in it. This matches the department tabs, which only let you write in the
department the project is in.

Each zone sub-tab has its own **Notes** and **Files**, scoped to project + zone:

- Notes are `NotesSection` in **zone mode** (`zoneId` set). Same component, same
  blade, same `@mention` + notification + e-mail behaviour as a department note —
  it just reads/writes `project_zone_notes`. The "Show to Customer" toggle is
  hidden in zone mode: the customer tracking page reads `department_notes`, so
  the toggle would promise something that cannot happen.
- Files are `EnhancedFilesSection` in **zone mode**, reading/writing
  `project_zone_files` on the same `projects/` disk folder.

Both are gated by `$showEditFields`, which in zone mode is
`$zoneId == $projectZoneId` — the project's current zone. That one flag drives
three things in each blade: the note box / upload button, the per-note
edit+delete icons, and the per-file delete + inline title editing. All three
carry a `!$zoneId ||` guard so **department tabs keep their existing behaviour
untouched**. Mentions raised from a zone note still fill
`notes_mentions.department_id` (with the project's department) so every existing
mention query keeps working, plus a new `zone_id`.

**Why separate tables and not a `zone_id` column on `department_notes` /
`project_files`:** those two are read by the department tabs, the customer
tracking page and the follow-up chases, several of them without an `ungrouped()`
guard. A zone row landing in them would surface in all three.

---

## 7. Zone fields

A zone may own **project fields** of its own. Today exactly one does, and there
is exactly one field: **NTP** owns `projects.ntp_approval_date`.

### Why it moved here

The NTP Approval Date used to be a **Deal Review department field**. It is a
funding fact, not an operations one, so it now belongs to the funding side
alone:

- it was removed from the Deal Review edit **and** view field panels
  (`livewire/project/project-fields/edit-fields.blade.php` / `view-fields.blade.php`)
  and from `EditFields`, which no longer holds or writes the property;
- migration `2026_08_28_000007_remove_ntp_approval_date_from_deal_review_fields`
  deleted its `project_department_fields` row, so a project can leave Deal
  Review without it;
- the column stays and every existing value stays.

**Operations still asks for the date, on the move that actually needs it.**
Permitting → Installation is refused without it and the move modal collects it
right there (`ProjectController::ntpApprovalGate`, ahead of the MPU chase — see
`docs/follow-ups.md`). That gate is the enforcement; the NTP zone tab is where
the funding side can see and fill the date ahead of time. The intake form and
the customer-create form still capture it on creation.

### The field in the tab

`config('zones.zone_fields')` maps a zone slug to the `projects` columns its tab
collects:

```php
'zone_fields' => [
    'ntp' => [
        'ntp_approval_date' => ['label' => 'NTP Approval Date', 'type' => 'date'],
    ],
],
```

The NTP tab renders a "**NTP Fields**" panel above Zone History, in the notes
column — the same `project-section-panel` / `department-fields-frame` frame the
department section uses. It follows the section's existing rule: **editable only
while the project is in that zone**, read-only (`@disabled`) in every other tab,
exactly like the notes and files beside it.

Saving posts to `POST /zones/fields` (`ZoneController::fields`), which refuses
anything but the project's **current** zone and writes only the columns that
zone declares in config — so the endpoint can never be talked into writing an
arbitrary project column. Each write gets its own activity-log line.

### No zone move is gated

Zone moves stay free in both directions, the archive included. A zone field is
something the funding side records, never something that blocks a zone move —
the NTP Approval Date is enforced on the department side, on Permitting →
Installation, and nowhere else.

---
## 8. Access

One permission, **`View Zones`**, granted by migration to **Funding Manager**
and **Admin**. Super Admin passes every gate already (`Gate::before` in
`AuthServiceProvider`).

The Funding Manager role also received the project-page permissions it needs to
work a project it opened from a zone (`View Project`, `Notes Section`,
`Files Section`, `View Financial Details`, …). **`Project Move` is deliberately
not among them** — a Funding Manager moves zones, never departments.

Two role shapes, two experiences (`User::isZoneOnlyUser()` /
`User::isFundingManager()`):

- **Funding Manager only** — no Operations side at all. They land on `/projects`
  like everyone else, but the page renders **only the Zones tab** (the
  Operational tab and its pane are not output at all, and `projectList()` is not
  even called), the sidebar shows only Zones, and on a project page the
  **Department Fields** panel and the **department move bar** are hidden.
- **Funding Manager + another role** — both tabs, Operational active by default
  (`?tab=zones` opens on Zones). Their other role's rights are completely
  untouched: they still see Department Fields and can still move departments.

Everyone else sees no trace of the module.

---

## 9. Gotchas

- **Zone rows are keyed by slug, not id.** `config/zones.php` and
  `Zone::isArchive()` look zones up by slug; never renumber or rename a slug
  without updating the config.
- **`php artisan view:cache` does not lint the compiled PHP.** After a Blade
  change here, compile then `php -l storage/framework/views/*.php` (same gotcha
  as the follow-ups work).
- **The Zones section shares the department section's CSS**, and the workspace
  redesign styles the detail tab bar by **id**, not by class. Every
  `#departmentDetailTabs` rule in `show.blade.php` therefore also lists
  `#zoneDetailTabs` — five rule groups around line 1400. Add a new
  `#departmentDetailTabs` rule and you must add `#zoneDetailTabs` beside it, or
  the zone tabs silently fall back to the plain `.project-department-tabs` look.
  The heading reuses `.department-detail-heading` and the labels reuse
  `.department-detail-tab-title`, both of which are class-scoped and need nothing.
- **The notes/files blades are shared with the department tabs.** Any change to
  `livewire/project/notes-section.blade.php` or `enhanced-files-section.blade.php`
  must keep the `$zoneId ? … : …` branch in `$showEditFields` intact, or one of
  the two modes silently loses its editor.
- **`customers` has no `name` column** — it is `first_name` + `last_name`. The
  board's search builds the full name by hand.
- **The board's cards are ajax-injected**, so the Move button's click handler in
  `move-modal.blade.php` is **delegated** off `document`. Binding it to the
  buttons directly works once and then silently dies on the next filter change.
- **The card's CSS is duplicated, on purpose.** The projects page keeps its card
  styles inside `project-list.blade.php`, which is itself an ajax fragment; the
  board cannot rely on that fragment having been loaded. `board.blade.php`
  therefore carries its own copy, scoped under `.zone-board`. If the projects
  page card is restyled, mirror it there.
- **`.btn-outline-*` is NOT themed.** `layouts/master.blade.php` paints
  `.btn-primary` / `.btn-success` / `.btn-dark` with the brand gradient but
  leaves outline buttons on Bootstrap blue (`#0d6efd`), which reads as a foreign
  colour in this warm palette. The board paints its own outline buttons; do the
  same for any new one.
- **Column headers are deliberately a soft tint, not the gradient.** Four
  saturated `--solen-gradient` bars side by side were too loud, so the header is
  `--solen-primary-soft` with warm text and the column body carries a 3.5%
  orange wash. The card header itself is untouched — it must keep matching the
  projects page.
- **A zone field has exactly one owner.** `ntp_approval_date` is the funding
  side's now; do not put it back in the department field panels. Two panels
  writing one column is how the value silently disagrees with itself.
- **Zone moves are never gated on a field.** If a project field has to be
  present before something happens, that belongs on the department move
  (`ProjectController`), not here. `ZoneController::move()` has no field logic
  at all — keep it that way.
- **Deploy:** this touches Blade views, so after pushing run `php artisan
  view:clear && php artisan cache:clear && php artisan config:clear` on the
  server (see the deployment gotcha in `CLAUDE.md`).

---

## 10. Adding a sixth zone

1. Insert the row in `zones` (slug, name, order, `show_in_list`) via a migration.
2. If it takes part in an automatic rule, extend `config/zones.php` and
   `ZoneService::handleDepartmentArrival()` — nothing else reads those rules.
   Fields of its own are config too: `zone_fields` (§7). A field listed there
   must not also live in the department field panels.
3. Add a case to `tests/Feature/ZoneWorkflowTest.php`.

Nothing else is hard-coded to five zones: the board, the move dropdown and the
project tabs are all driven by the table.
