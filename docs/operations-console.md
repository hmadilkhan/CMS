# Operations Console

One window for the Operations screens — the same idea the report builder got:
what there is on the left, what you are working on in the middle, no trip back
to the sidebar between two settings screens.

**Phase 1 (this).** The console lists every Operations screen and opens the one
you pick *in place*, by loading that screen's own page embedded. Nothing was
rewritten to get there: each screen keeps its route, its controller, its
permissions and its own URL, and still works when opened directly.

---

## Where the code is

| Concern | File |
|---|---|
| What the console holds | `config/operations_console.php` |
| Which sections this viewer sees | `app/Services/OperationsConsoleService.php` |
| The page | `app/Http/Controllers/OperationsConsoleController.php`, `resources/views/operations/console.blade.php` |
| Embedded rendering | `resources/views/layouts/master.blade.php` (`?embedded=1`) |
| Route | `routes/web.php` — `operations.console`, `can:User Management` |
| Tests | `tests/Feature/OperationsConsoleTest.php` |

## The config IS the console

`config/operations_console.php` lists groups and, under them, sections:

```php
['key' => 'departments', 'label' => 'Departments', 'route' => 'departments.list', 'icon' => 'icofont-network-tower'],
```

- `key` is what `/operations?section=<key>` asks for, so a section is a place
  that can be linked to and that Back returns to.
- `route` is the screen's existing route name — the console never hardcodes a
  URL. A section whose route no longer exists is **dropped**, not rendered as a
  dead link, and a test fails if the config names one.
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

## What Phase 1 deliberately does not do

- It does not touch a single Operations screen. That is the point: the console
  could ship complete instead of arriving one screen at a time.
- The middle pane is an iframe, so the two pages do not share scroll position,
  and a screen that navigates inside the frame keeps the console's URL. Both are
  the trade for covering twenty-one screens at once.

## Turning a section native (later phases)

Replace the iframe for one section at a time:

1. Move that screen's inner content into a partial or a Livewire component.
2. Give its section a `component` key in the config and render that instead of
   the frame.
3. Leave the screen's own route working — the "Open full page" link, bookmarks
   and any deep link from elsewhere in the CRM still use it.

Nothing else in the console changes, and the sections not yet converted keep
working exactly as they do now.
