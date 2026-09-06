# Report builder UI mockup

Working files for the proposed report builder UI, shown to the team as a design
canvas: https://claude.ai/code/artifact/7cf5d97b-bdb1-4e74-9d4e-4272ef03aa8d

- `Main.dc.html` — the builder: outline + fields, live preview with subtotals, filters
- `Filters.dc.html` — one filter being edited, with the multi-value picker
- `Results.dc.html` — the run view: chart, grouped table, totals, exports
- `canvas.json` — how the three sit on the canvas

Colours, type and spacing come from the app's own tokens
(`resources/views/layouts/partials/solen-palette.blade.php`), so the mockup and
the CRM stay the same product. Numbers and names in them are sample data.

The published canvas is built from these files; the built file itself is not
committed (2.5 MB) — it is regenerated from them.
