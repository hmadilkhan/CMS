@extends('layouts.master')
@section('title', 'Transaction Portal')
{{-- Full screen: no sidebar or header; the back link below replaces them. --}}
@section('chromeless', '1')
@section('content')
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Figtree:wght@400;500;600;700;800&family=IBM+Plex+Mono:wght@400;500;600&display=swap">
<style>
/* Layout: header with week navigator -> summary tiles -> one lane per section (money in, money out), each lane footed by its total. Board / Table are two views of the same rows. Every rule is scoped to .tp so the CRM's Bootstrap classes are left alone. */
.tp {
    --bg: #f8f4ef;
    --surface: #ffffff;
    --surface-2: #fcf9f5;
    --fg: #342416;
    --muted: #7c6f60;
    --line: #eadfce;
    --line-strong: #d6c4ab;
    --accent: #ee8f45;
    --accent-deep: #c8642d;
    --accent-soft: #fdf0e3;
    --on-accent: #2b1a0c;
    --in: #2f7a4f;
    --out: #a94f1f;
    --danger: #b63f2a;
    --danger-soft: #fbe7e2;
    --warn: #9a5f08;
    --warn-soft: #fbf0d8;
    --ok: #2f7a4f;
    --ok-soft: #e6f2ea;
    --scrim: rgba(52, 36, 22, .32);
    --shadow: 0 1px 2px rgba(52, 36, 22, .05), 0 6px 18px rgba(52, 36, 22, .06);
    --font-ui: "Figtree", system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    --font-num: "IBM Plex Mono", ui-monospace, "Cascadia Mono", Consolas, monospace;

    background: var(--bg); color: var(--fg); font-family: var(--font-ui); font-size: 14px; line-height: 1.45;
    border-radius: 14px; padding: 18px 16px 110px; display: grid; gap: 18px;
    width: 100%; min-height: calc(100vh - 16px); align-content: start;
    /* One column no wider than the page: a wide board scrolls inside its own box, never the page. */
    grid-template-columns: minmax(0, 1fr);
}
.tp > * { min-width: 0; }
.tp *, .tp *::before, .tp *::after { box-sizing: border-box; }
.tp button, .tp input, .tp select { font: inherit; color: inherit; }
.tp .mono { font-family: var(--font-num); font-variant-numeric: tabular-nums; }
.tp-sr { position: absolute !important; width: 1px; height: 1px; overflow: hidden; clip: rect(0 0 0 0); white-space: nowrap; }

.tp .crumbs { display: flex; flex-wrap: wrap; align-items: center; gap: 10px; font-size: 12.5px; color: var(--muted); }
.tp .crumbs b { color: var(--fg); font-weight: 600; }
.tp .back { display: inline-flex; align-items: center; gap: 6px; border: 1px solid var(--line); background: var(--surface); border-radius: 999px; padding: 4px 12px 4px 9px; color: var(--fg); font-weight: 600; font-size: 12.5px; text-decoration: none; }
.tp .back:hover { border-color: var(--accent); color: var(--accent-deep); }
.tp .back svg { width: 14px; height: 14px; }

.tp .head { display: grid; grid-template-columns: 1fr auto 1fr; align-items: center; gap: 16px; }
.tp .head h1 { margin: 0; font-size: 26px; font-weight: 800; letter-spacing: -.01em; color: var(--fg); font-family: var(--font-ui); }
.tp .head .sub { margin: 2px 0 0; color: var(--muted); }
.tp .tp-nav { display: grid; justify-items: center; gap: 8px; }
.tp .nav-row { display: flex; align-items: center; gap: 10px; }
.tp .icon-btn { width: 38px; height: 38px; border-radius: 50%; border: 1px solid var(--line); background: var(--surface); display: grid; place-items: center; cursor: pointer; font-size: 18px; line-height: 1; padding: 0; }
.tp .icon-btn:hover { border-color: var(--accent); color: var(--accent-deep); }
.tp .range { text-align: center; min-width: 228px; }
.tp .range .dates { font-family: var(--font-num); font-size: 15px; font-weight: 600; }
.tp .range .rel { font-size: 12px; color: var(--muted); }
.tp .nav-opts { display: flex; align-items: center; gap: 8px; }
.tp .tools { display: flex; justify-content: flex-end; flex-wrap: wrap; gap: 8px; }

.tp .seg { display: inline-flex; border: 1px solid var(--line); border-radius: 9px; background: var(--surface); padding: 2px; }
.tp .seg button { border: 0; background: none; padding: 5px 11px; border-radius: 7px; cursor: pointer; font-size: 13px; color: var(--muted); font-weight: 600; }
.tp .seg button[aria-pressed="true"] { background: var(--accent-soft); color: var(--accent-deep); }
.tp .link-btn { border: 0; background: none; color: var(--accent-deep); font-weight: 600; cursor: pointer; font-size: 13px; padding: 4px 6px; text-decoration: none; }
.tp .tp-btn { border: 1px solid var(--line); background: var(--surface); border-radius: 9px; padding: 7px 13px; font-weight: 600; font-size: 13px; cursor: pointer; color: var(--fg); text-decoration: none; display: inline-flex; align-items: center; }
.tp .tp-btn:hover { border-color: var(--line-strong); color: var(--fg); }
.tp .tp-btn.primary { background: var(--accent); border-color: var(--accent); color: var(--on-accent); }
.tp .tp-btn.primary:hover { filter: brightness(.96); }
.tp .tp-btn[disabled] { opacity: .6; cursor: progress; }
.tp .tp-btn.danger { background: var(--danger); border-color: var(--danger); color: #fff; }
.tp .tp-btn.danger:hover { filter: brightness(.95); color: #fff; }
.tp .search { position: relative; }
.tp .search input { width: 220px; max-width: 100%; border: 1px solid var(--line); background: var(--surface); border-radius: 9px; padding: 7px 11px 7px 30px; }
.tp .search svg { position: absolute; left: 10px; top: 50%; transform: translateY(-50%); width: 14px; height: 14px; color: var(--muted); pointer-events: none; }

.tp .filters { display: flex; flex-wrap: wrap; align-items: center; gap: 10px 16px; padding: 10px 12px; border: 1px solid var(--line); border-radius: 12px; background: var(--surface); }
.tp .filters label { display: inline-flex; align-items: center; gap: 7px; font-size: 13px; color: var(--muted); margin: 0; }
.tp .filters select { border: 1px solid var(--line); background: var(--surface-2); border-radius: 8px; padding: 5px 9px; color: var(--fg); }
.tp .filters .lbl { font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); font-weight: 700; }
.tp .filters input[type="checkbox"] { accent-color: var(--accent); width: 15px; height: 15px; }

.tp .tiles { display: grid; grid-template-columns: repeat(var(--tiles, 4), minmax(0, 1fr)); gap: 12px; }
.tp .tile { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 14px 16px; display: grid; gap: 4px; min-width: 0; }
.tp .tile .k { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: var(--muted); display: flex; align-items: center; gap: 6px; }
.tp .tile .v { font-family: var(--font-num); font-variant-numeric: tabular-nums; font-size: 23px; font-weight: 600; letter-spacing: -.02em; }
.tp .tile .s { font-size: 12.5px; color: var(--muted); }
.tp .tile.net { background: var(--accent-soft); border-color: var(--accent); }
.tp .tile.net .v.neg { color: var(--danger); }
.tp .dot { width: 8px; height: 8px; border-radius: 50%; display: inline-block; flex: none; }
.tp .dot.in { background: var(--in); }
.tp .dot.out { background: var(--out); }
.tp .dot.acc { background: var(--accent); }
.tp .bad-text { color: var(--danger); font-weight: 600; }

.tp .board-scroll { overflow-x: auto; padding-bottom: 6px; scroll-snap-type: x proximity; }
.tp .board { display: grid; grid-template-columns: repeat(var(--lanes, 5), minmax(236px, 1fr)); gap: 12px; align-items: stretch; transition: opacity .15s; }
/* A card never grows past its lane: a long project name is cut with "…". */
.tp .item .main { grid-template-columns: minmax(0, 1fr); }
.tp .board.single { grid-template-columns: minmax(0, 640px); }
.tp .board.loading, .tp .table-wrap.loading { opacity: .55; }
.tp .grp { display: flex; align-items: center; gap: 10px; font-size: 11px; text-transform: uppercase; letter-spacing: .1em; font-weight: 800; padding: 0 2px; }
.tp .grp::after { content: ""; flex: 1; height: 1px; background: var(--line-strong); }
.tp .grp.in { color: var(--in); }
.tp .grp.out { color: var(--out); }
.tp .lane { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; display: flex; flex-direction: column; min-width: 0; scroll-snap-align: start; box-shadow: var(--shadow); }
.tp .lane-head { padding: 12px 14px 10px; border-bottom: 1px solid var(--line); display: grid; gap: 6px; }
.tp .lane-title { display: flex; align-items: center; gap: 8px; }
.tp .lane-title h2 { margin: 0; font-size: 15px; font-weight: 700; flex: 1; min-width: 0; color: var(--fg); font-family: var(--font-ui); }
.tp .tp-count { font-family: var(--font-num); font-size: 11.5px; border: 1px solid var(--line); border-radius: 999px; padding: 0 8px; color: var(--muted); }
.tp .fields { margin: 0; font-size: 11.5px; color: var(--muted); line-height: 1.35; }
.tp .lane-search { width: 100%; border: 1px solid var(--line); background: var(--surface-2); border-radius: 8px; padding: 5px 9px; font-size: 12.5px; }
.tp .lane-body { flex: 1; padding: 8px; display: flex; flex-direction: column; gap: 6px; min-height: 230px; }
.tp .sect { margin: 6px 4px 0; font-size: 11px; text-transform: uppercase; letter-spacing: .07em; font-weight: 700; color: var(--muted); }
.tp .sect.carry { color: var(--danger); }
.tp .sect:first-child { margin-top: 2px; }
.tp .empty { margin: auto; color: var(--muted); font-size: 13px; text-align: center; padding: 24px 8px; }
.tp .item { display: flex; align-items: flex-start; gap: 9px; border: 1px solid var(--line); border-radius: 9px; background: var(--surface-2); padding: 9px 10px; transition: border-color .15s; }
.tp .item:hover { border-color: var(--line-strong); }
.tp .item.late { border-color: #eab8ad; }
.tp .item.closed { opacity: .6; }
.tp .item input[type="checkbox"] { margin-top: 2px; accent-color: var(--accent); width: 16px; height: 16px; flex: none; cursor: pointer; }
.tp .paid-mark { width: 16px; height: 16px; flex: none; margin-top: 2px; border-radius: 4px; background: var(--ok-soft); color: var(--ok); font-size: 11px; display: grid; place-items: center; font-weight: 800; }
.tp .main { all: unset; flex: 1; min-width: 0; display: grid; gap: 4px; cursor: pointer; }
.tp .main:focus-visible { outline: 2px solid var(--accent); outline-offset: 4px; border-radius: 4px; }
.tp .l1 { display: flex; align-items: baseline; gap: 8px; }
.tp .proj { flex: 1; min-width: 0; font-weight: 650; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
.tp .amt { font-family: var(--font-num); font-variant-numeric: tabular-nums; font-weight: 600; font-size: 13px; white-space: nowrap; }
.tp .meta { font-size: 12px; color: var(--muted); }
.tp .chips { display: flex; flex-wrap: wrap; gap: 5px; }
.tp .chip { font-size: 11px; font-weight: 700; border-radius: 6px; padding: 1px 7px; background: var(--bg); color: var(--muted); border: 1px solid var(--line); white-space: nowrap; }
.tp .chip.bad { background: var(--danger-soft); color: var(--danger); border-color: transparent; }
.tp .chip.warn { background: var(--warn-soft); color: var(--warn); border-color: transparent; }
.tp .chip.ok { background: var(--ok-soft); color: var(--ok); border-color: transparent; }
.tp .chip.run { background: var(--accent-soft); color: var(--accent-deep); border-color: transparent; }
.tp .chip.st { background: none; font-weight: 600; }
.tp .lane-foot { border-top: 1px dashed var(--line-strong); padding: 10px 14px 12px; display: grid; grid-template-columns: 1fr auto; align-items: baseline; gap: 2px 8px; }
.tp .lane-foot span { font-size: 12px; text-transform: uppercase; letter-spacing: .07em; font-weight: 700; color: var(--muted); }
.tp .lane-foot strong { font-family: var(--font-num); font-variant-numeric: tabular-nums; font-size: 16px; }
.tp .lane-foot small { grid-column: 1 / -1; text-align: right; color: var(--muted); font-size: 12px; font-family: var(--font-num); }

.tp .table-top { display: flex; flex-wrap: wrap; gap: 8px; align-items: center; justify-content: space-between; }
.tp .tp-tabs { display: flex; gap: 6px; overflow-x: auto; padding-bottom: 2px; }
.tp .tp-tab { border: 1px solid var(--line); background: var(--surface); border-radius: 10px; padding: 8px 12px; cursor: pointer; text-align: left; display: grid; gap: 1px; white-space: nowrap; }
.tp .tp-tab b { font-size: 13px; display: flex; align-items: center; gap: 6px; }
.tp .tp-tab small { font-family: var(--font-num); color: var(--muted); font-size: 12px; }
.tp .tp-tab[aria-selected="true"] { border-color: var(--accent); background: var(--accent-soft); }
.tp .table-wrap { overflow-x: auto; border: 1px solid var(--line); border-radius: 12px; background: var(--surface); }
.tp .table-wrap table { width: 100%; border-collapse: collapse; min-width: 760px; margin: 0; }
.tp .table-wrap th, .tp .table-wrap td { padding: 10px 12px; text-align: left; border-bottom: 1px solid var(--line); white-space: nowrap; color: var(--fg); }
.tp .table-wrap th { font-size: 11px; text-transform: uppercase; letter-spacing: .07em; color: var(--muted); font-weight: 700; background: var(--surface-2); }
.tp .table-wrap td.num, .tp .table-wrap th.num { text-align: right; font-family: var(--font-num); font-variant-numeric: tabular-nums; }
.tp .table-wrap tbody tr:hover { background: var(--surface-2); }
.tp .table-wrap tr.closed td { opacity: .6; }
.tp .table-wrap tr.group td { background: var(--bg); font-size: 11px; text-transform: uppercase; letter-spacing: .07em; font-weight: 700; color: var(--danger); }
.tp .table-wrap tr.group.plain td { color: var(--muted); }
.tp .table-wrap tfoot td { font-weight: 700; border-bottom: 0; }
.tp .cell-link { all: unset; cursor: pointer; font-weight: 650; }
.tp .cell-link:hover { color: var(--accent-deep); text-decoration: underline; }
.tp .cell-link:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
.tp .table-wrap input[type="checkbox"] { accent-color: var(--accent); width: 16px; height: 16px; }

.tp .bulk { position: fixed; left: 50%; transform: translateX(-50%); bottom: calc(18px + env(safe-area-inset-bottom, 0px)); background: var(--fg); color: var(--bg); border-radius: 12px; padding: 9px 10px 9px 16px; display: flex; align-items: center; gap: 12px; box-shadow: 0 10px 30px rgba(0, 0, 0, .25); z-index: 100400; max-width: calc(100% - 32px); flex-wrap: wrap; }
.tp .bulk .mono { font-weight: 600; }
.tp .bulk .link-btn { color: var(--bg); opacity: .8; }
.tp .bulk label { display: inline-flex; align-items: center; gap: 6px; margin: 0; font-size: 13px; }
.tp .bulk input[type="date"] { border: 0; border-radius: 7px; padding: 4px 6px; color: var(--fg); background: var(--surface); }

.tp .scrim { position: fixed; inset: 0; background: var(--scrim); z-index: 100410; }
.tp .drawer { position: fixed; top: 0; right: 0; bottom: 0; width: min(440px, 100%); background: var(--surface); border-left: 1px solid var(--line); z-index: 100420; display: flex; flex-direction: column; transform: translateX(100%); transition: transform .22s ease; visibility: hidden; }
.tp .drawer.open { transform: none; visibility: visible; }
.tp .d-head { padding: 16px 18px 14px; border-bottom: 1px solid var(--line); display: grid; gap: 4px; }
.tp .d-top { display: flex; align-items: center; gap: 10px; }
.tp .eyebrow { font-size: 11px; text-transform: uppercase; letter-spacing: .08em; font-weight: 700; color: var(--muted); display: flex; align-items: center; gap: 6px; flex: 1; }
.tp .d-head h3 { margin: 0; font-size: 20px; font-weight: 800; color: var(--fg); font-family: var(--font-ui); }
.tp .d-amt { font-family: var(--font-num); font-size: 26px; font-weight: 600; letter-spacing: -.02em; }
.tp .d-body { padding: 16px 18px; overflow-y: auto; flex: 1; display: grid; gap: 18px; align-content: start; }
.tp .d-body dl { margin: 0; display: grid; grid-template-columns: minmax(130px, auto) 1fr; gap: 9px 14px; }
.tp .d-body dt { color: var(--muted); font-size: 12.5px; font-weight: 400; }
.tp .d-body dd { margin: 0; font-weight: 600; min-width: 0; overflow-wrap: anywhere; }
.tp .box { border: 1px solid var(--line); border-radius: 12px; padding: 14px; display: grid; gap: 10px; background: var(--surface-2); }
.tp .box h4 { margin: 0; font-size: 14px; color: var(--fg); font-family: var(--font-ui); font-weight: 700; }
.tp .box p { margin: 0; color: var(--muted); font-size: 12.5px; }
.tp .field { display: grid; gap: 4px; }
.tp .field label { font-size: 12px; color: var(--muted); font-weight: 600; margin: 0; }
.tp .field input { border: 1px solid var(--line); border-radius: 8px; padding: 8px 10px; background: var(--surface); }
.tp .row2 { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.tp .file { display: flex; align-items: center; gap: 8px; font-size: 12.5px; font-family: var(--font-num); overflow-wrap: anywhere; }
.tp .d-foot { padding: 12px 18px; border-top: 1px solid var(--line); display: flex; gap: 8px; justify-content: flex-end; flex-wrap: wrap; }

.tp .tp-toast { position: fixed; left: 16px; bottom: calc(18px + env(safe-area-inset-bottom, 0px)); background: var(--surface); border: 1px solid var(--line); box-shadow: var(--shadow); border-radius: 10px; padding: 10px 14px; z-index: 100430; max-width: min(420px, calc(100% - 32px)); font-size: 13px; color: var(--fg); }
.tp .no-sections { background: var(--surface); border: 1px solid var(--line); border-radius: 12px; padding: 28px; text-align: center; color: var(--muted); }

.tp button:focus-visible, .tp input:focus-visible, .tp select:focus-visible { outline: 2px solid var(--accent); outline-offset: 2px; }
.tp [hidden] { display: none !important; }

@media (max-width: 1080px) {
    .tp .head { grid-template-columns: 1fr; justify-items: start; }
    .tp .tp-nav { justify-items: start; }
    .tp .tools { justify-content: flex-start; }
    .tp .tiles { grid-template-columns: repeat(2, minmax(0, 1fr)); }
}
@media (max-width: 520px) {
    .tp .tiles { grid-template-columns: 1fr; }
    .tp .board { grid-template-columns: repeat(var(--lanes, 5), minmax(84vw, 1fr)); }
    .tp .range { min-width: 0; }
    .tp .search input { width: 100%; }
    .tp .search { flex: 1 1 100%; }
    .tp .row2 { grid-template-columns: 1fr; }
}
@media (prefers-reduced-motion: reduce) { .tp .drawer, .tp .item, .tp .board { transition: none; } }
</style>

<div class="tp" id="tp">
    <div class="crumbs">
        <a class="back" href="{{ $backUrl }}"><svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path d="M10 3L5 8l5 5"></path></svg>Back to {{ $backLabel }}</a>
        <span>Finance</span><span aria-hidden="true">›</span><b>Transaction Portal</b>
    </div>

    <header class="head">
        <div>
            <h1>Transaction Portal</h1>
            <p class="sub">Money coming in and going out, week by week.</p>
        </div>
        <div class="tp-nav">
            <div class="nav-row">
                <button class="icon-btn" id="prev" type="button" aria-label="Previous period">‹</button>
                <div class="range" aria-live="polite"><div class="dates" id="dates"></div><div class="rel" id="rel"></div></div>
                <button class="icon-btn" id="next" type="button" aria-label="Next period">›</button>
            </div>
            <div class="nav-opts">
                <div class="seg" role="group" aria-label="Period">
                    <button type="button" data-mode="week" aria-pressed="true">Week</button>
                    <button type="button" data-mode="month" aria-pressed="false">Month</button>
                </div>
                <button class="link-btn" id="today" type="button">Go to today</button>
            </div>
        </div>
        <div class="tools">
            <div class="search">
                <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="7" cy="7" r="5"></circle><path d="M11 11l3.5 3.5"></path></svg>
                <input id="q" type="search" placeholder="Search all dates…" title="Searches every week and month, in every section" aria-label="Search every date, in all sections">
            </div>
            <div class="seg" role="group" aria-label="View">
                <button type="button" data-view="board" aria-pressed="true">Board</button>
                <button type="button" data-view="table" aria-pressed="false">Table</button>
            </div>
            <button class="tp-btn" id="export" type="button">Export</button>
            @can('Account Transactions Edit')
                <a class="tp-btn" href="{{ route('transaction-portal.import') }}" style="text-decoration:none">Import</a>
            @endcan
        </div>
    </header>

    @if (empty($sections))
        <div class="no-sections">
            No section of the Transaction Portal is assigned to you yet. Ask an admin for
            <b>Transaction Portal Expected AR</b> or <b>Transaction Portal Other Sections</b>.
        </div>
    @else
        <div class="filters">
            <span class="lbl">Filter</span>
            <div class="seg" role="group" aria-label="Status">
                <button type="button" data-status="all" aria-pressed="true">All</button>
                <button type="button" data-status="open" aria-pressed="false">Open</button>
                <button type="button" data-status="overdue" aria-pressed="false">Overdue</button>
                <button type="button" data-status="payrun" aria-pressed="false">Pay run</button>
                <button type="button" data-status="closed" aria-pressed="false">Closed</button>
            </div>
            <label>Finance option
                <select id="fin">
                    <option value="">All</option>
                    @foreach ($financeOptions as $option)
                        <option value="{{ $option }}">{{ $option }}</option>
                    @endforeach
                </select>
            </label>
            <label><input type="checkbox" id="carry" checked> Show open items from earlier weeks</label>
        </div>

        <section class="tiles" id="tiles" aria-label="Summary"></section>

        <section id="board-view">
            <div class="board-scroll"><div class="board" id="board"></div></div>
        </section>

        <section id="table-view" hidden>
            <div style="display:grid;gap:10px">
                <div class="table-top">
                    <div class="tp-tabs" role="tablist" id="tabs"></div>
                    <div class="search">
                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><circle cx="7" cy="7" r="5"></circle><path d="M11 11l3.5 3.5"></path></svg>
                        <input id="tq" type="search" aria-label="Search this section">
                    </div>
                </div>
                <div class="table-wrap" id="table-wrap"><table id="tbl"></table></div>
            </div>
        </section>
    @endif

    <div class="scrim" id="scrim" hidden></div>
    <aside class="drawer" id="drawer" aria-label="Transaction details" aria-hidden="true"></aside>
    <div class="bulk" id="bulk" hidden></div>
    <div class="tp-toast" id="toast" role="status" hidden></div>
</div>
@endsection

@section('scripts')
<script>
(function () {
    const CFG = @json($portalConfig);
    const CSRF = (document.querySelector('meta[name="csrf_token"]') || {}).content || '';
    const SECTIONS = CFG.sections;

    function d(s) { const [y, m, dd] = s.split('-').map(Number); return new Date(y, m - 1, dd); }
    function iso(x) { return x.getFullYear() + '-' + String(x.getMonth() + 1).padStart(2, '0') + '-' + String(x.getDate()).padStart(2, '0'); }
    function addDays(x, n) { const y = new Date(x); y.setDate(y.getDate() + n); return y; }
    function us(s) { if (!s) return '—'; const x = typeof s === 'string' ? d(s) : s; return String(x.getMonth() + 1).padStart(2, '0') + '/' + String(x.getDate()).padStart(2, '0') + '/' + x.getFullYear(); }
    function days(a, b) { return Math.round((b - a) / 864e5); }
    const TODAY = d(CFG.today);
    const money = n => '$' + Number(n).toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const esc = s => String(s == null ? '' : s).replace(/[&<>"']/g, c => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const txt = (v, fallback) => v ? esc(v) : '<span style="color:var(--muted)">' + (fallback || '—') + '</span>';
    const plural = (n, w) => n + ' ' + w + (n === 1 ? '' : 's');
    const sum = list => list.reduce((t, r) => t + Number(r.amount), 0);
    const $ = id => document.getElementById(id);

    const B = {
        ear: { title: 'Expected Accounts Receivable', short: 'Expected AR', kind: 'in', fields: 'Project Name · Finance Option · Expected Receivable Date · Amount · Status' },
        aar: { title: 'Actual Accounts Receivable', short: 'Actual AR', kind: 'in', fields: 'Sales Partner · Project Name · Finance Option · Actual Received Amount · Date Received · Status' },
        com: { title: 'Commission Payable', short: 'Commission', kind: 'out', fields: 'Project Name · Payment Received Date · Payable Amount · Due Date · Days Overdue · Status' },
        lab: { title: 'Labor Payable', short: 'Labor', kind: 'out', fields: 'Project Name · Inspection Approval Date · Payable Amount · Due Date · Days Overdue · Status' },
        mat: { title: 'Material Invoices Payable', short: 'Material', kind: 'out', fields: 'Project Name · Invoice Date · Invoice Amount · Due Date · Days Overdue · Status' },
        oth: { title: 'Other Payables', short: 'Other', kind: 'out', fields: 'Project Name · Payee · Milestone · Date · Payable Amount · Days Overdue · Status' }
    };
    // The money-out sections that count toward "Payables due" and a pay run.
    const PAYABLES = ['com', 'lab', 'mat', 'oth'];

    let rows = [];
    let loaded = false;
    let loadSeq = 0;
    const S = { mode: 'week', anchor: new Date(TODAY), view: 'board', status: 'all', fin: '', q: '', laneQ: {}, carry: true, sel: new Set(), tab: SECTIONS[0], drawer: null, all: false };
    // The top search box (2+ letters) searches every date on the server; rows then hold its results.
    let truncated = false;
    let searchTimer;

    const byId = id => rows.find(r => r.id === id);
    // The date a row is placed in a week by. A paid payable sits in the week it was paid.
    const key = r => (r.b !== 'ear' && r.b !== 'aar' && r.status === 'Closed' && r.paidOn) ? r.paidOn
        : ({ ear: r.expected, aar: r.received, com: r.due || r.paymentReceived, lab: r.inspection, mat: r.due, oth: r.due })[r.b];
    // The date a row is late after; labor has none yet.
    const dueOf = r => r.b === 'ear' ? r.expected : (r.b === 'aar' ? null : r.due);
    const isOverdue = r => r.status === 'Open' && !!dueOf(r) && d(dueOf(r)) < TODAY;
    // Pay run: payables picked (on Thursday) to be paid on a day of the period shown.
    const inPeriod = s => { const [a, b] = range(), x = d(s); return x >= a && x <= b; };
    const inPayRun = r => !!r.payRun && inPeriod(r.payRun);
    const runChip = r => r.payRun && r.status === 'Open' && (inPeriod(r.payRun) || d(r.payRun) >= TODAY)
        ? '<span class="chip run">Pay run ' + us(r.payRun) + '</span>' : '';
    // The next Friday from today (today when it is Friday): the default pay run day.
    const nextFriday = () => addDays(TODAY, (5 - TODAY.getDay() + 7) % 7);

    function range() {
        if (S.mode === 'week') { const s = addDays(S.anchor, -S.anchor.getDay()); return [s, addDays(s, 6)]; }
        return [new Date(S.anchor.getFullYear(), S.anchor.getMonth(), 1), new Date(S.anchor.getFullYear(), S.anchor.getMonth() + 1, 0)];
    }
    function relLabel() {
        const [s] = range();
        if (S.mode === 'week') {
            const n = Math.round(days(addDays(TODAY, -TODAY.getDay()), s) / 7);
            return n === 0 ? 'This week' : n === -1 ? 'Last week' : n === 1 ? 'Next week' : n < 0 ? plural(-n, 'week') + ' ago' : 'In ' + plural(n, 'week');
        }
        const n = (s.getFullYear() - TODAY.getFullYear()) * 12 + s.getMonth() - TODAY.getMonth();
        const name = s.toLocaleString('en-US', { month: 'long', year: 'numeric' });
        return n === 0 ? name + ' · this month' : name;
    }

    const matches = (r, q) => [r.project, r.partner, r.sub, r.finance, r.milestone, r.invoiceNo, r.file, r.payee].join(' ').toLowerCase().includes(q.toLowerCase());
    function passes(r) {
        if (S.fin && r.finance !== S.fin) return false;
        // In "All time" the server already searched (more fields than this), so do not filter again.
        if (S.q && !S.all && !matches(r, S.q)) return false;
        if (S.laneQ[r.b] && !matches(r, S.laneQ[r.b])) return false;
        if (S.status === 'open') return r.status === 'Open';
        if (S.status === 'closed') return r.status === 'Closed';
        if (S.status === 'overdue') return isOverdue(r);
        if (S.status === 'payrun') return inPayRun(r);
        return true;
    }
    function lane(b) {
        if (S.all) {
            // Search results: one list per section, newest first, no week split.
            const found = rows.filter(r => r.b === b && passes(r)).sort((x, y) => d(key(y)) - d(key(x)));
            return { inRange: found, carry: [], all: found };
        }
        const [s] = range();
        const list = rows.filter(r => r.b === b && passes(r)).sort((x, y) => d(key(x)) - d(key(y)));
        const isCarry = r => d(key(r)) < s;
        const inRange = list.filter(r => !isCarry(r));
        const carry = S.carry ? list.filter(isCarry) : [];
        return { inRange, carry, all: carry.concat(inRange) };
    }

    function chip(r) {
        // Received, plus what helps spot a duplicate: where it came from, and a twin amount.
        if (r.b === 'aar') return '<span class="chip ok">Received</span>' +
            (r.imported ? '<span class="chip">Imported</span>' : '') +
            (r.twin ? '<span class="chip warn" title="Another payment on this project has the same amount">Same amount</span>' : '');
        if (r.status === 'Closed') return '<span class="chip ok">Paid' + (r.paidOn ? ' ' + us(r.paidOn) : '') + '</span>';
        const due = dueOf(r);
        if (!due) return '';
        const n = days(d(due), TODAY), verb = r.b === 'ear' ? 'Expected' : 'Due';
        if (n > 0) return '<span class="chip bad">' + plural(n, 'day') + ' overdue</span>';
        if (n === 0) return '<span class="chip warn">' + verb + ' today</span>';
        return '<span class="chip ' + (-n <= 3 ? 'warn' : '') + '">' + verb + ' in ' + plural(-n, 'day') + '</span>';
    }
    const statusChip = r => '<span class="chip st">' + esc(r.status) + '</span>';
    function meta(r) {
        switch (r.b) {
            case 'ear': return txt(r.finance, 'No finance option') + ' · Expected ' + us(r.expected);
            case 'aar': return txt(r.partner, 'No sales partner') + ' · ' + txt(r.finance, 'No finance option') + ' · Received ' + us(r.received);
            case 'com': return txt(r.partner, 'No sales partner') + ' · Payment rec’d ' + us(r.paymentReceived) + ' · Due ' + us(r.due);
            case 'lab': return txt(r.sub, 'No sub-contractor') + ' · Inspection approved ' + us(r.inspection);
            case 'oth': return txt(r.payee, 'No payee') + (r.milestone ? ' · ' + esc(r.milestone) : '') + ' · Due ' + us(r.due);
            default: return 'Invoice ' + us(r.invoiceDate) + ' · Due ' + us(r.due);
        }
    }
    const daysOver = r => isOverdue(r) ? '<span class="bad-text">' + plural(days(d(dueOf(r)), TODAY), 'day') + '</span>' : '—';
    const link = r => '<button type="button" class="cell-link" data-open="' + esc(r.id) + '">' + esc(r.project) + '</button>';

    const COLS = {
        ear: [['Project Name', link], ['Finance Option', r => txt(r.finance)], ['Expected Receivable Date', r => us(r.expected)], ['Amount', r => money(r.amount), 'num'], ['Status', r => chip(r) || statusChip(r)]],
        aar: [['Sales Partner', r => txt(r.partner)], ['Project Name', link], ['Finance Option', r => txt(r.finance)], ['Actual Received Amount', r => money(r.amount), 'num'], ['Date Received', r => us(r.received)], ['Status', statusChip]],
        com: [['Project Name', link], ['Sales Partner', r => txt(r.partner)], ['Payment Received Date', r => us(r.paymentReceived)], ['Payable Amount', r => money(r.amount), 'num'], ['Due Date', r => us(r.due)], ['Days Overdue', daysOver], ['Status', r => (chip(r) || statusChip(r)) + runChip(r)]],
        lab: [['Project Name', link], ['Sub-Contractor', r => txt(r.sub)], ['Inspection Approval Date', r => us(r.inspection)], ['Payable Amount', r => money(r.amount), 'num'], ['Due Date', r => us(r.due)], ['Days Overdue', daysOver], ['Status', r => (chip(r) || statusChip(r)) + runChip(r)]],
        mat: [['Project Name', link], ['Invoice Date', r => us(r.invoiceDate)], ['Invoice Amount', r => money(r.amount), 'num'], ['Due Date', r => us(r.due)], ['Days Overdue', daysOver], ['Status', r => (chip(r) || statusChip(r)) + runChip(r)]],
        oth: [['Project Name', link], ['Payee', r => txt(r.payee)], ['Milestone', r => txt(r.milestone)], ['Date', r => us(r.date)], ['Payable Amount', r => money(r.amount), 'num'], ['Days Overdue', daysOver], ['Status', r => (chip(r) || statusChip(r)) + runChip(r)]]
    };

    // Rows that take a tick box: every payable, and Expected AR (bulk "Mark as received").
    // Actual AR too, for whoever may delete received payments (e.g. duplicates).
    const canPick = b => b === 'ear' || B[b].kind === 'out' || (b === 'aar' && !!CFG.canDelete);
    function pick(r) {
        if (!canPick(r.b)) return '';
        if (r.status !== 'Open' && r.b !== 'aar') return '<span class="paid-mark" aria-hidden="true">✓</span>';
        return '<input type="checkbox" class="pick" data-id="' + esc(r.id) + '"' + (S.sel.has(r.id) ? ' checked' : '') + ' aria-label="Select ' + esc(r.project) + ' ' + B[r.b].short + '">';
    }
    function itemHTML(r) {
        return '<div class="item' + (r.status === 'Open' ? '' : ' closed') + (isOverdue(r) ? ' late' : '') + '">' + pick(r) +
            '<button type="button" class="main" data-open="' + esc(r.id) + '">' +
            '<span class="l1"><span class="proj">' + esc(r.project) + '</span><span class="amt">' + money(r.amount) + '</span></span>' +
            '<span class="meta">' + meta(r) + '</span>' +
            '<span class="chips">' + chip(r) + runChip(r) + (r.b !== 'aar' ? statusChip(r) : '') + '</span></button></div>';
    }

    // The lanes are built once, so typing in a lane's search box never loses focus.
    function buildBoard() {
        const ins = SECTIONS.filter(b => B[b].kind === 'in').length, outs = SECTIONS.length - ins;
        const board = $('board');
        board.style.setProperty('--lanes', SECTIONS.length);
        board.classList.toggle('single', SECTIONS.length === 1);
        board.innerHTML = (ins ? '<div class="grp in" style="grid-column:span ' + ins + '">Money in</div>' : '') +
            (outs ? '<div class="grp out" style="grid-column:span ' + outs + '">Money out</div>' : '') +
            SECTIONS.map(b => '<section class="lane" aria-label="' + B[b].title + '"><header class="lane-head"><div class="lane-title"><span class="dot ' + B[b].kind + '"></span><h2>' + B[b].title + '</h2><span class="tp-count" id="lc-' + b + '">0</span></div>' +
                '<p class="fields">' + B[b].fields + '</p>' +
                '<input type="search" class="lane-search" data-lane="' + b + '" id="ls-' + b + '" placeholder="Search ' + B[b].short + '…" aria-label="Search ' + B[b].title + '"></header>' +
                '<div class="lane-body" id="lb-' + b + '"><p class="empty">Loading…</p></div><footer class="lane-foot" id="lf-' + b + '"></footer></section>').join('');
    }
    function renderLane(b) {
        const L = lane(b);
        const open = sum(L.all.filter(r => r.status === 'Open'));
        let body = '';
        if (L.carry.length) {
            body += '<p class="sect carry">Earlier, still open · ' + L.carry.length + '</p>' + L.carry.map(itemHTML).join('');
            if (L.inRange.length) body += '<p class="sect">In this ' + S.mode + '</p>';
        }
        body += L.inRange.map(itemHTML).join('');
        if (!L.all.length) body += '<p class="empty">' + emptyText() + '</p>';
        $('lc-' + b).textContent = L.all.length;
        $('lb-' + b).innerHTML = body;
        $('lf-' + b).innerHTML = '<span>Total</span><strong>' + money(sum(L.all)) + '</strong>' +
            (b === 'aar' ? '' : '<small>' + (b === 'ear' ? 'Still expected ' : 'Unpaid ') + money(open) + '</small>');
    }

    const emptyText = () => !S.all ? 'Nothing in this ' + S.mode + '.'
        : (S.q.length < 2 ? 'Type at least 2 letters to search.' : 'No match in any week.');

    function renderSearchTiles() {
        const found = SECTIONS.flatMap(b => lane(b).all), open = found.filter(r => r.status === 'Open');
        const tiles = [
            '<div class="tile net"><span class="k"><span class="dot acc"></span>All-time search</span><span class="v">' + plural(found.length, 'result') + '</span><span class="s">' +
                (S.q.length < 2 ? 'Type at least 2 letters to search every week and month.'
                    : 'for “' + esc(S.q) + '” in every week and month' + (truncated ? ' · showing the newest ' + CFG.searchLimit + ', narrow the search to see more' : '')) + '</span></div>',
            '<div class="tile"><span class="k"><span class="dot out"></span>Still open</span><span class="v">' + money(sum(open)) + '</span><span class="s">' + plural(open.length, 'item') + ' not yet received or paid</span></div>'
        ];
        $('tiles').style.setProperty('--tiles', tiles.length);
        $('tiles').innerHTML = tiles.join('');
    }

    function renderTiles() {
        if (S.all) { renderSearchTiles(); return; }
        const has = b => SECTIONS.includes(b);
        const L = {}; SECTIONS.forEach(b => L[b] = lane(b));
        const openOf = b => has(b) ? L[b].all.filter(r => r.status === 'Open') : [];
        const late = SECTIONS.flatMap(b => L[b].all.filter(isOverdue));
        const lateLine = late.length ? '<span class="bad-text">' + plural(late.length, 'item') + ' overdue · ' + money(sum(late)) + '</span>' : 'Nothing overdue';
        const tiles = [];
        if (has('ear')) {
            tiles.push('<div class="tile"><span class="k"><span class="dot in"></span>Expected in</span><span class="v">' + money(sum(openOf('ear'))) + '</span><span class="s">' + plural(openOf('ear').length, 'payment') + ' still to arrive</span></div>');
        }
        if (has('aar')) {
            const due = sum(PAYABLES.flatMap(openOf));
            tiles.push('<div class="tile"><span class="k"><span class="dot in"></span>Received</span><span class="v">' + money(sum(L.aar.inRange)) + '</span><span class="s">' + plural(L.aar.inRange.length, 'deposit') + ' in this ' + S.mode + '</span></div>');
            tiles.push('<div class="tile"><span class="k"><span class="dot out"></span>Payables due</span><span class="v">' + money(due) + '</span><span class="s">Commission ' + money(sum(openOf('com'))) + ' · Labor ' + money(sum(openOf('lab'))) + ' · Material ' + money(sum(openOf('mat'))) + ' · Other ' + money(sum(openOf('oth'))) + '</span></div>');
            // Net = cash received in the period minus what is still owed out.
            const net = sum(L.aar.inRange) - due;
            tiles.push('<div class="tile net"><span class="k"><span class="dot acc"></span>Net position</span><span class="v' + (net < 0 ? ' neg' : '') + '">' + (net < 0 ? '−' : '') + money(Math.abs(net)) + '</span><span class="s">' + lateLine + '</span></div>');
            // The pay run: what is planned to go out this week. Its other open payables moved to next week.
            const run = PAYABLES.filter(has).flatMap(b => L[b].all.filter(inPayRun));
            if (run.length) {
                const toPay = run.filter(r => r.status === 'Open'), paid = run.length - toPay.length;
                const runDays = [...new Set(run.map(r => r.payRun))].sort();
                tiles.push('<div class="tile"><span class="k"><span class="dot out"></span>Pay run ' + (runDays.length === 1 ? us(runDays[0]) : '') + '</span><span class="v">' + money(sum(toPay)) + '</span><span class="s">' + plural(toPay.length, 'payable') + ' to pay' + (paid ? ' · ' + paid + ' paid' : '') + '. Other open payables moved to next week.</span></div>');
            }
        }
        if (tiles.length < 2) tiles.push('<div class="tile"><span class="k"><span class="dot acc"></span>Overdue</span><span class="v">' + money(sum(late)) + '</span><span class="s">' + lateLine + '</span></div>');
        $('tiles').style.setProperty('--tiles', tiles.length);
        $('tiles').innerHTML = tiles.join('');
    }

    function renderTable() {
        $('tabs').innerHTML = SECTIONS.map(b => {
            const L = lane(b);
            return '<button type="button" role="tab" class="tp-tab" data-tab="' + b + '" aria-selected="' + (S.tab === b) + '"><b><span class="dot ' + B[b].kind + '"></span>' + B[b].short + ' · ' + L.all.length + '</b><small>' + money(sum(L.all)) + '</small></button>';
        }).join('');
        const b = S.tab, cols = COLS[b], L = lane(b), out = canPick(b);
        const amtIdx = cols.findIndex(c => c[2] === 'num');
        const tr = r => '<tr class="' + (r.status === 'Open' ? '' : 'closed') + '">' + (out ? '<td>' + pick(r) + '</td>' : '') + cols.map(c => '<td class="' + (c[2] || '') + '">' + c[1](r) + '</td>').join('') + '</tr>';
        const span = cols.length + (out ? 1 : 0);
        let body = '';
        if (L.carry.length) body += '<tr class="group"><td colspan="' + span + '">Earlier, still open · ' + L.carry.length + '</td></tr>' + L.carry.map(tr).join('') + (L.inRange.length ? '<tr class="group plain"><td colspan="' + span + '">In this ' + S.mode + '</td></tr>' : '');
        body += L.inRange.map(tr).join('');
        if (!L.all.length) body = '<tr><td colspan="' + span + '" style="text-align:center;color:var(--muted);padding:28px">' + emptyText() + '</td></tr>';
        const lead = amtIdx + (out ? 1 : 0);
        $('tbl').innerHTML = '<thead><tr>' + (out ? '<th><span class="tp-sr">Select</span></th>' : '') + cols.map(c => '<th class="' + (c[2] || '') + '">' + c[0] + '</th>').join('') + '</tr></thead><tbody>' + body + '</tbody>' +
            '<tfoot><tr><td colspan="' + lead + '">Total</td><td class="num">' + money(sum(L.all)) + '</td><td colspan="' + (span - lead - 1) + '"></td></tr></tfoot>';
        const tq = $('tq');
        tq.placeholder = 'Search ' + B[b].short + '…';
        if (document.activeElement !== tq) tq.value = S.laneQ[b] || '';
    }

    function renderBulk() {
        const bar = $('bulk');
        const picked = [...S.sel].map(byId).filter(r => r && r.status === 'Open');
        const dels = receivedPicked();
        if (!picked.length && !dels.length) { bar.hidden = true; bar.innerHTML = ''; return; }
        // Money in (Expected AR payments) and money out (payables) are counted apart;
        // the pay run is for payables only; received payments (Actual AR) can only be deleted.
        const ins = picked.filter(r => r.b === 'ear'), outs = picked.filter(r => r.b !== 'ear');
        const verb = !outs.length ? 'received' : (!ins.length ? 'paid' : 'received / paid');
        const keepDate = $('bulk-date') ? $('bulk-date').value : iso(TODAY);
        const keepRun = $('bulk-run-date') ? $('bulk-run-date').value : iso(nextFriday());
        const what = [
            ins.length ? plural(ins.length, 'payment') + ' · <span class="mono">' + money(sum(ins)) + '</span>' : '',
            outs.length ? plural(outs.length, 'payable') + ' · <span class="mono">' + money(sum(outs)) + '</span>' : '',
            dels.length ? plural(dels.length, 'received payment') + ' · <span class="mono">' + money(sum(dels)) + '</span>' : ''
        ].filter(Boolean).join(' and ');
        bar.hidden = false;
        bar.innerHTML = '<span>' + what + ' selected</span>' +
            (outs.length
                ? '<label for="bulk-run-date">Pay run <input type="date" id="bulk-run-date" min="' + iso(TODAY) + '" value="' + esc(keepRun) + '"></label>' +
                  '<button type="button" class="tp-btn" id="bulk-run">Add to pay run</button>' +
                  (outs.some(r => r.payRun) ? '<button type="button" class="link-btn" id="bulk-unrun">Remove from pay run</button>' : '')
                : '') +
            (picked.length
                ? '<label for="bulk-date">' + (verb === 'paid' ? 'Paid on' : verb === 'received' ? 'Received on' : 'Received / paid on') + ' <input type="date" id="bulk-date" value="' + esc(keepDate) + '"></label>'
                : '') +
            '<button type="button" class="link-btn" id="bulk-clear">Clear</button>' +
            (dels.length ? '<button type="button" class="tp-btn danger" id="bulk-delete">Delete ' + plural(dels.length, 'received payment') + '</button>' : '') +
            (picked.length ? '<button type="button" class="tp-btn primary" id="bulk-paid">Mark as ' + verb + '</button>' : '');
    }

    // Ticked received payments (Actual AR), the only rows that can be deleted here.
    const receivedPicked = () => [...S.sel].map(byId).filter(r => r && r.b === 'aar');

    // Confirm, then delete received payments. Warns when a commission was raised
    // after one of them was received: deleting the payment does not take it back.
    async function deleteReceived(ids, button) {
        const list = ids.map(byId).filter(r => r && r.b === 'aar');
        if (!list.length) return;
        const raised = [...new Set(list.filter(r => r.commissionAfter).map(r => r.project))];
        const message = 'Delete ' + plural(list.length, 'received payment') + ' (' + money(sum(list)) + ')?\n\n' +
            list.slice(0, 8).map(r => '• ' + r.project + ' · ' + (r.milestone || '') + ' · ' + money(r.amount) + ' · received ' + us(r.received)).join('\n') +
            (list.length > 8 ? '\n• … and ' + (list.length - 8) + ' more' : '') +
            '\n\nThey are removed from Account Transactions (the activity log keeps a record).' +
            (raised.length ? '\n\nCommission was already raised on ' + raised.join(', ') + ' after these were received. Deleting the payment does NOT take that commission back - check Commission Payable.' : '');
        if (!window.confirm(message)) return;
        if (button) button.disabled = true;
        try {
            const json = await post(CFG.deleteUrl, { items: list.map(r => r.id) });
            list.forEach(r => S.sel.delete(r.id));
            closeDrawer();
            toast(plural(json.deleted, 'received payment') + ' deleted · ' + money(sum(list)) + '.');
            await load();
        } catch (err) {
            toast(err.message);
        } finally {
            if (button) button.disabled = false;
        }
    }

    function render() {
        const [s, e] = range();
        $('dates').textContent = S.all ? 'All time' : us(s) + ' – ' + us(e);
        $('rel').textContent = S.all ? 'Search results from every week and month · clear the search to go back' : relLabel();
        $('export').disabled = S.all;
        if (!SECTIONS.length || !loaded) return;
        renderTiles();
        if (S.view === 'board') SECTIONS.forEach(renderLane); else renderTable();
        $('board-view').hidden = S.view !== 'board';
        $('table-view').hidden = S.view !== 'table';
        renderBulk();
        if (S.drawer) { if (byId(S.drawer)) renderDrawer(); else closeDrawer(); }
    }

    async function load() {
        render();
        if (!SECTIONS.length) return;
        const seq = ++loadSeq, [s, e] = range();
        if (S.all && S.q.length < 2) { rows = []; truncated = false; loaded = true; render(); return; }
        $('board').classList.add('loading'); $('table-wrap').classList.add('loading');
        try {
            const url = S.all
                ? CFG.searchUrl + '?q=' + encodeURIComponent(S.q)
                : CFG.dataUrl + '?start=' + iso(s) + '&end=' + iso(e);
            const res = await fetch(url, { headers: { Accept: 'application/json' } });
            if (!res.ok) throw new Error(res.status);
            const json = await res.json();
            if (seq !== loadSeq) return;
            rows = json.rows;
            truncated = !!json.truncated;
            S.sel.forEach(id => { const r = byId(id); if (!r || (r.status !== 'Open' && r.b !== 'aar')) S.sel.delete(id); });
        } catch (err) {
            if (seq === loadSeq) toast('The transactions could not be loaded. Refresh the page to try again.');
        } finally {
            if (seq === loadSeq) { loaded = true; $('board').classList.remove('loading'); $('table-wrap').classList.remove('loading'); render(); }
        }
    }

    async function post(url, body) {
        const res = await fetch(url, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: JSON.stringify(body)
        });
        const json = await res.json().catch(() => ({}));
        if (!res.ok) {
            const first = json.errors ? Object.values(json.errors)[0][0] : null;
            throw new Error(first || json.message || (res.status === 403 ? 'You do not have permission to close rows in this section.' : 'Something went wrong. Try again.'));
        }
        return json;
    }

    // drawer
    function renderDrawer() {
        const r = byId(S.drawer); if (!r) return;
        const info = B[r.b], out = info.kind === 'out', open = r.status === 'Open';
        const fields = COLS[r.b].map(c => '<dt>' + c[0] + '</dt><dd>' + (c[0] === 'Project Name' ? esc(r.project) : c[1](r)) + '</dd>');
        if (r.milestone && !COLS[r.b].some(c => c[0] === 'Milestone')) fields.splice(1, 0, '<dt>Milestone</dt><dd>' + esc(r.milestone) + '</dd>');
        if (r.details) fields.push('<dt>Details</dt><dd>' + esc(r.details) + '</dd>');
        let extra = '';
        if (r.b === 'ear' && open) {
            extra = '<form class="box" id="settle-form"><h4>Record payment received</h4><p>Moves this payment to Actual AR. When the Labor and Material invoices are on file, the sales partner’s commission is raised, due the Friday of the following week.</p>' +
                '<div class="row2"><div class="field"><label for="st-amt">Amount received</label><input id="st-amt" type="number" step="0.01" min="0.01" value="' + Number(r.amount).toFixed(2) + '" required></div>' +
                '<div class="field"><label for="st-date">Date received</label><input id="st-date" type="date" value="' + iso(TODAY) + '" required></div></div>' +
                '<div><button class="tp-btn primary" type="submit">Record payment</button></div></form>';
        } else if (out && open) {
            extra = '<form class="box" id="settle-form"><h4>Mark as paid</h4><p>Closes this payable with the amount paid and the day it went out.</p>' +
                '<div class="row2"><div class="field"><label for="st-amt">Amount paid</label><input id="st-amt" type="number" step="0.01" min="0.01" value="' + Number(r.amount).toFixed(2) + '" required></div>' +
                '<div class="field"><label for="st-date">Date paid</label><input id="st-date" type="date" value="' + iso(TODAY) + '" required></div></div>' +
                '<div><button class="tp-btn primary" type="submit">Mark as paid</button></div></form>';
            extra += r.payRun && d(r.payRun) >= TODAY
                ? '<div class="box"><h4>In the pay run for ' + us(r.payRun) + '</h4><p>Take it out and it moves to next week with the other open payables.</p><div><button type="button" class="tp-btn" data-act="unrun">Remove from pay run</button></div></div>'
                : '<div class="box"><h4>Pay run</h4><p>Plan to pay it on this day. Once a week has a pay run, its other open payables move to next week.</p>' +
                    '<div class="field"><label for="run-date">Pay on</label><input id="run-date" type="date" min="' + iso(TODAY) + '" value="' + iso(nextFriday()) + '"></div>' +
                    '<div><button type="button" class="tp-btn" data-act="run">Add to pay run</button></div></div>';
        }
        if (r.file) extra += '<div class="box"><h4>Invoice file</h4><span class="file">📄 ' + esc(r.file) + '</span><p>Uploaded on the project’s Invoices tab.</p></div>';
        if (out && !open) extra += '<div class="box"><h4>Paid' + (r.paidOn ? ' on ' + us(r.paidOn) : '') + '</h4><p>Reopen it if it was marked by mistake.</p></div>';
        const actions = (out && !open ? '<button type="button" class="tp-btn" data-act="reopen">Reopen</button>' : '') +
            (r.b === 'aar' && CFG.canDelete ? '<button type="button" class="tp-btn danger" data-act="delete">Delete</button>' : '') +
            (r.projectUrl ? '<a class="tp-btn" href="' + esc(r.projectUrl) + '" target="_blank" rel="noopener">Open project</a>' : '');
        $('drawer').innerHTML =
            '<div class="d-head"><div class="d-top"><span class="eyebrow"><span class="dot ' + info.kind + '"></span>' + info.title + '</span><button type="button" class="icon-btn" data-act="close" aria-label="Close details">×</button></div>' +
            '<h3>' + esc(r.project) + '</h3><div class="d-amt">' + money(r.amount) + '</div><div class="chips">' + chip(r) + statusChip(r) + '</div></div>' +
            '<div class="d-body"><dl>' + fields.join('') + '</dl>' + extra + '</div><div class="d-foot">' + actions + '</div>';
    }
    function openDrawer(id) {
        S.drawer = id; renderDrawer();
        const dr = $('drawer'); dr.classList.add('open'); dr.setAttribute('aria-hidden', 'false'); $('scrim').hidden = false;
        const c = dr.querySelector('[data-act="close"]'); if (c) c.focus();
    }
    function closeDrawer() {
        S.drawer = null; const dr = $('drawer'); dr.classList.remove('open'); dr.setAttribute('aria-hidden', 'true'); $('scrim').hidden = true;
    }

    let tt;
    function toast(msg) { const t = $('toast'); t.textContent = msg; t.hidden = false; clearTimeout(tt); tt = setTimeout(() => t.hidden = true, 4500); }

    async function settle(ids, date, amount, button) {
        if (button) button.disabled = true;
        try {
            const list = ids.map(byId).filter(Boolean);
            const json = await post(CFG.settleUrl, { items: ids, date: date, amount: amount });
            const single = list.length === 1 ? list[0] : null;
            const ins = list.filter(r => r.b === 'ear'), outs = list.filter(r => r.b !== 'ear');
            let msg = single && single.b === 'ear'
                ? 'Payment recorded for ' + single.project + '.'
                : [
                    ins.length ? plural(ins.length, 'payment') + ' recorded as received · ' + money(sum(ins)) : '',
                    outs.length ? plural(outs.length, 'payable') + ' marked paid · ' + money(sum(outs)) : ''
                ].filter(Boolean).join('; ') + '.';
            (json.commissions || []).forEach(c => { msg += ' Commission payable raised: ' + money(c.amount) + ', due ' + us(c.due) + '.'; });
            ids.forEach(id => S.sel.delete(id));
            closeDrawer();
            toast(msg);
            await load();
        } catch (err) {
            toast(err.message);
        } finally {
            if (button) button.disabled = false;
        }
    }

    // Back to the period: clears the top search, which is what put the page on every date.
    function leaveAllTime() {
        if (!S.all) return;
        S.all = false; S.q = ''; S.sel.clear(); clearTimeout(searchTimer);
        $('q').value = '';
    }

    // Put payables in the pay run for `date`, or take them out (date null).
    async function payRun(ids, date, button) {
        if (button) button.disabled = true;
        try {
            const json = await post(CFG.payRunUrl, { items: ids, date: date });
            ids.forEach(id => S.sel.delete(id));
            closeDrawer();
            toast(date
                ? plural(json.updated, 'payable') + ' added to the ' + us(date) + ' pay run. Other open payables of that week move to the next week.'
                : plural(json.updated, 'payable') + ' taken out of the pay run.');
            await load();
        } catch (err) {
            toast(err.message);
        } finally {
            if (button) button.disabled = false;
        }
    }

    // events
    document.addEventListener('click', e => {
        if (!e.target.closest('#tp')) return;
        const t = e.target.closest('button'); if (!t) return;
        if (t.dataset.open) { openDrawer(t.dataset.open); return; }
        // Choosing a period leaves the all-time search and goes back to that week or month.
        if (t.id === 'prev' || t.id === 'next') {
            const n = t.id === 'prev' ? -1 : 1;
            if (S.all) leaveAllTime();
            else S.anchor = S.mode === 'week' ? addDays(S.anchor, 7 * n) : new Date(S.anchor.getFullYear(), S.anchor.getMonth() + n, 1);
            load(); return;
        }
        if (t.id === 'today') { leaveAllTime(); S.anchor = new Date(TODAY); load(); return; }
        if (t.dataset.mode) { leaveAllTime(); S.mode = t.dataset.mode; pressed('[data-mode]', t); load(); return; }
        if (t.dataset.view) { S.view = t.dataset.view; pressed('[data-view]', t); render(); return; }
        if (t.dataset.status) { S.status = t.dataset.status; pressed('[data-status]', t); render(); return; }
        if (t.dataset.tab) { S.tab = t.dataset.tab; render(); return; }
        if (t.id === 'export') { const [s, en] = range(); window.location.href = CFG.exportUrl + '?start=' + iso(s) + '&end=' + iso(en); return; }
        if (t.id === 'bulk-clear') { S.sel.clear(); render(); return; }
        if (t.id === 'bulk-paid') {
            const date = $('bulk-date').value;
            if (!date) { toast('Choose the date they were received or paid.'); return; }
            settle([...S.sel].filter(id => (byId(id) || {}).status === 'Open'), date, null, t); return;
        }
        if (t.id === 'bulk-run') {
            const date = $('bulk-run-date').value;
            if (!date) { toast('Choose the day of the pay run.'); return; }
            payRun([...S.sel].filter(id => { const r = byId(id); return r && B[r.b].kind === 'out' && r.status === 'Open'; }), date, t); return;
        }
        if (t.id === 'bulk-delete') { deleteReceived(receivedPicked().map(r => r.id), t); return;
        }
        if (t.id === 'bulk-unrun') { payRun([...S.sel].filter(id => (byId(id) || {}).payRun), null, t); return; }
        const act = t.dataset.act;
        if (act === 'close') closeDrawer();
        else if (act === 'run') {
            const date = $('run-date').value;
            if (!date) { toast('Choose the day of the pay run.'); return; }
            payRun([S.drawer], date, t);
        }
        else if (act === 'unrun') payRun([S.drawer], null, t);
        else if (act === 'delete') deleteReceived([S.drawer], t);
        else if (act === 'reopen') {
            const r = byId(S.drawer);
            t.disabled = true;
            post(CFG.reopenUrl, { item: r.id })
                .then(() => { closeDrawer(); toast(r.project + ' reopened.'); return load(); })
                .catch(err => { toast(err.message); t.disabled = false; });
        }
    });
    function pressed(sel, btn) { document.querySelectorAll('#tp ' + sel).forEach(b => b.setAttribute('aria-pressed', String(b === btn))); }

    document.addEventListener('change', e => {
        const t = e.target;
        if (!t.closest || !t.closest('#tp')) return;
        if (t.classList.contains('pick')) { const id = t.dataset.id; t.checked ? S.sel.add(id) : S.sel.delete(id); renderBulk(); }
        else if (t.id === 'fin') { S.fin = t.value; render(); }
        else if (t.id === 'carry') { S.carry = t.checked; render(); }
    });
    document.addEventListener('input', e => {
        const t = e.target;
        if (t.id === 'q') {
            // Two letters or more search every date; fewer (or a cleared box) is the period shown.
            S.q = t.value.trim();
            const wasAll = S.all;
            S.all = S.q.length >= 2;
            clearTimeout(searchTimer);
            if (S.all) { if (!wasAll) S.sel.clear(); searchTimer = setTimeout(load, 350); }
            else if (wasAll) { S.sel.clear(); load(); }
            else render();
        }
        else if (t.classList && t.classList.contains('lane-search')) { S.laneQ[t.dataset.lane] = t.value.trim(); if (loaded) { renderTiles(); renderLane(t.dataset.lane); } }
        else if (t.id === 'tq') { S.laneQ[S.tab] = t.value.trim(); const ls = $('ls-' + S.tab); if (ls) ls.value = t.value; render(); }
    });
    document.addEventListener('submit', e => {
        if (e.target.id !== 'settle-form') return;
        e.preventDefault();
        const amt = parseFloat($('st-amt').value), date = $('st-date').value;
        if (!(amt > 0) || !date) { toast('Enter the amount and the date.'); return; }
        settle([S.drawer], date, amt, e.target.querySelector('button[type="submit"]'));
    });
    $('scrim').addEventListener('click', closeDrawer);
    document.addEventListener('keydown', e => { if (e.key === 'Escape' && S.drawer) closeDrawer(); });

    if (SECTIONS.length) buildBoard();
    load();
})();
</script>
@endsection
