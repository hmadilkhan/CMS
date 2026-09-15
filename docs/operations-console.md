# Operations Console

One window for the Operations screens — the same idea the report builder got:
what there is on the left, what you are working on in the middle, no trip back
to the sidebar between two settings screens.

A section is drawn one of two ways, and `config/operations_console.php` alone
decides which:

- **a panel** — the console draws the screen itself, inside the console page:
  one scroll, one set of scripts, one history. Today the whole **Pipeline**
  group (Departments, Sub Departments, Assign Department) and the whole
  **Pricing** group (Adders, Adder Types, Dealer Fee, Office Cost, Labor Cost).
- **a frame** — the console loads the screen's own page embedded
  (`?embedded=1`), without the sidebar and header. Everything else.

That split is why the console could ship covering all twenty-one screens at
once: a screen joins as a frame, and is promoted to a panel later without
anything outside the config changing. Either way the screen keeps its route, its
controller, its permissions and its own URL, and still works opened directly.

---

## Where the code is

| Concern | File |
|---|---|
| What the console holds | `config/operations_console.php` |
| Which sections this viewer sees | `app/Services/OperationsConsoleService.php` |
| The page | `app/Http/Controllers/OperationsConsoleController.php`, `resources/views/operations/console.blade.php` |
| A screen drawn natively | `app/Services/Operations/*Panel.php` + `resources/views/operations/<screen>/panel.blade.php` |
| Coming back to the console after a save | `app/Http/Controllers/Concerns/ReturnsToOperationsConsole.php` |
| Embedded rendering | `resources/views/layouts/master.blade.php` (`?embedded=1`) |
| Route | `routes/web.php` — `operations.console`, `can:User Management` |
| Tests | `tests/Feature/OperationsConsoleTest.php` |

## The config IS the console

`config/operations_console.php` lists groups and, under them, sections:

```php
['key' => 'departments', 'label' => 'Departments', 'route' => 'departments.list', 'icon' => 'icofont-network-tower', 'panel' => DepartmentsPanel::class],
['key' => 'loan-terms',  'label' => 'Loan Terms',  'route' => 'loan.term',        'icon' => 'icofont-calendar'],
```

- `key` is what `/operations?section=<key>` asks for, so a section is a place
  that can be linked to and that Back returns to.
- `route` is the screen's existing route name — the console never hardcodes a
  URL. A section whose route no longer exists is **dropped**, not rendered as a
  dead link, and a test fails if the config names one.
- `panel` (optional) is the `OperationsPanel` that draws the screen inside the
  console page. Without it the section is loaded in a frame. This key is the
  *only* difference between the two, and nothing outside this file has to know
  which a section is.
- `permission` (optional) overrides `operations_console.permission`
  (`User Management` today). The console shows a viewer only what they may open;
  it grants nothing of its own — every screen still enforces its own middleware.

Adding, renaming, regrouping or reordering a screen is an edit to this file.

## `?embedded=1`

`layouts.master` renders without the sidebar and the header when the request
carries `embedded=1`, and marks the layout `is-embedded` so nothing is reserved
for a sidebar that is not there. That is the whole mechanism — any page on that
layout can be shown inside another one.

It is a *view* flag, not an access flag: the page still runs its own middleware,
so embedding cannot show anyone a screen they could not open directly.

## A panel

A panel is two things: a **partial** holding the screen's body with no page
chrome around it (`operations/departments/panel.blade.php`), and an
**`OperationsPanel`** saying what that partial needs
(`app/Services/Operations/DepartmentsPanel.php`).

The screen's own page and the console both go through the same panel — the page
is now just `@include` of the partial, and its controller reads its data from
`app(DepartmentsPanel::class)->data($request)`. That is the point: there is one
Departments screen, not two that drift apart.

The partial gets one variable, `$console`, and it decides **only where links and
saves come back to** — never what is shown, and never who may see it:

- inside the console, its edit and Cancel links point at
  `/operations?section=<key>&id=…`, so `?id=` selects a record for editing
  exactly as the screen's own `/{id?}` does; on its own page they point at the
  screen.
- inside the console the form carries a hidden `ops_section`, and the
  controller's redirect goes through `ReturnsToOperationsConsole`, so a save
  lands back in the console instead of throwing the user out to the screen. The
  key comes from the browser, so it is honoured **only when it names a section
  the console really has for that viewer** — it can never become a redirect
  somewhere else.

Everything else keeps working because the console is on `layouts.master` too:
`.select2`, `.datatable` and the Bootstrap delete modal are initialised there,
for the panel as for any page. A screen's `@section('scripts')` moves into the
partial as a plain `<script>`; it still runs after the markup it acts on, and
the delete helpers only touch jQuery when they are called.

One thing that init did **not** survive contact with a narrow table: it asked
DataTables to right-align columns `-1` and `-3`, so a two-column table (Office
Cost, Labor Cost) threw and took the rest of that script with it — including the
`sidebar-mini` setup below it. The targets are now filtered against the table's
own column count, which leaves every wider table exactly as it was.

### Promoting a section

1. Move the screen's body from `index.blade.php` into `panel.blade.php`
   (the page becomes an `@extends` + `@include`), taking its `@section('scripts')`
   with it as a plain `<script>`.
2. Point its links and its Cancel at a `$screenUrl` helper, and add the hidden
   `ops_section` field under `@if ($console)`.
3. Add an `OperationsPanel` with the view name and the data the controller used
   to build, and have the controller use it too.
4. Redirect writes through `ReturnsToOperationsConsole`.
5. Name the panel in `config/operations_console.php`.

The screen's own route keeps working throughout — "Open full page", bookmarks
and deep links from elsewhere in the CRM still use it — and the sections not yet
promoted keep working exactly as they do now.

## What the console still does not do

- A framed section's page does not share the console's scroll, and a screen that
  navigates inside the frame keeps the console's URL. Promoting it to a panel is
  what fixes that.
- Switching between sections is a page load, not an in-place swap, once a panel
  is involved: a panel is drawn by the server with its own scripts, so it is
  loaded the way any page is. Framed sections still swap in place.
