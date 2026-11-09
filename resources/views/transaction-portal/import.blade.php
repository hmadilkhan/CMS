@extends('layouts.master')
@section('title', 'Import Transactions')
{{-- Full screen like the Transaction Portal it belongs to. --}}
@section('chromeless', '1')
@section('content')
<style>
.tpi { --fg: #342416; --muted: #7c6f60; --line: #eadfce; --accent: #ee8f45; --accent-deep: #c8642d; --soft: #fdf0e3;
    --ok: #2f7a4f; --ok-soft: #e6f2ea; --warn: #9a5f08; --warn-soft: #fbf0d8; --bad: #b63f2a; --bad-soft: #fbe7e2;
    background: #f8f4ef; color: var(--fg); min-height: 100vh; padding: 20px 24px 48px; font-size: 14px; }
.tpi a { color: var(--accent-deep); }
.tpi .crumbs { display: flex; gap: 8px; align-items: center; color: var(--muted); font-size: 13px; margin-bottom: 14px; }
.tpi .crumbs a { font-weight: 600; text-decoration: none; }
.tpi h1 { font-size: 24px; font-weight: 800; margin: 0; }
.tpi .sub { color: var(--muted); margin: 4px 0 18px; }
.tpi .card-x { background: #fff; border: 1px solid var(--line); border-radius: 14px; padding: 20px; margin-bottom: 16px; }
.tpi .card-x h2 { font-size: 16px; font-weight: 700; margin: 0 0 12px; }
.tpi .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; align-items: end; }
.tpi label { font-weight: 700; font-size: 13px; margin-bottom: 6px; display: block; }
.tpi .form-control, .tpi .form-select { border-color: var(--line); border-radius: 10px; min-height: 40px; }
.tpi .btn-tp { background: var(--accent); border: 0; color: #2b1a0c; font-weight: 700; border-radius: 999px; padding: 9px 20px; }
.tpi .btn-tp:hover { background: var(--accent-deep); color: #fff; }
.tpi .btn-ghost { background: #fff; border: 1px solid var(--line); color: var(--fg); font-weight: 600; border-radius: 999px; padding: 8px 18px; text-decoration: none; display: inline-block; }
.tpi .help { color: var(--muted); font-size: 13px; margin: 10px 0 0; }
.tpi .help code { background: var(--soft); color: var(--accent-deep); padding: 1px 6px; border-radius: 6px; }
.tpi .stats { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 14px; }
.tpi .stat { background: #fff; border: 1px solid var(--line); border-radius: 12px; padding: 10px 16px; min-width: 130px; }
.tpi .stat b { display: block; font-size: 20px; }
.tpi .stat span { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .04em; font-weight: 700; }
.tpi .table-wrap { overflow-x: auto; }
.tpi table { width: 100%; border-collapse: collapse; background: #fff; }
.tpi th { background: var(--soft); color: #7c2d12; font-size: 12px; text-transform: uppercase; letter-spacing: .03em; padding: 10px; white-space: nowrap; border-bottom: 1px solid var(--line); }
.tpi td { padding: 9px 10px; border-bottom: 1px solid #f1e8dc; vertical-align: top; }
.tpi tr.is-skip td { background: #fffaf8; color: var(--muted); }
.tpi .num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; }
.tpi .chip { display: inline-block; padding: 2px 9px; border-radius: 999px; font-size: 12px; font-weight: 700; white-space: nowrap; }
.tpi .chip.ok { background: var(--ok-soft); color: var(--ok); }
.tpi .chip.warn { background: var(--warn-soft); color: var(--warn); }
.tpi .chip.bad { background: var(--bad-soft); color: var(--bad); }
.tpi .chip.new { background: var(--soft); color: var(--accent-deep); }
.tpi .note { font-size: 12px; color: var(--muted); margin-top: 3px; }
.tpi .note.bad { color: var(--bad); }
.tpi .note.warn { color: var(--warn); }
.tpi .proj-select { min-width: 220px; }
.tpi .choice { display: flex; gap: 18px; flex-wrap: wrap; }
.tpi .choice label { font-weight: 600; display: flex; gap: 8px; align-items: center; margin: 0; }
.tpi .actions { display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 16px; }
@media (max-width: 640px) { .tpi { padding: 16px; } }
</style>

<div class="tpi">
    <div class="crumbs">
        <a href="{{ route('transaction-portal.index') }}">‹ Back to Transaction Portal</a>
        <span>Finance</span><span aria-hidden="true">›</span><b>Import Transactions</b>
    </div>
    <h1>Import Account Transactions</h1>
    <p class="sub">Upload a spreadsheet, check every row, then import. Nothing is saved until you press Import.</p>

    @if (session('importResult'))
        @php $result = session('importResult'); @endphp
        <div class="card-x">
            <h2>Imported {{ $result['created'] }} {{ \Illuminate\Support\Str::plural('transaction', $result['created']) }} from {{ $result['file'] }}</h2>
            @if (count($result['skipped']))
                <p class="help">{{ count($result['skipped']) }} {{ \Illuminate\Support\Str::plural('row', count($result['skipped'])) }} were not imported:</p>
                <div class="table-wrap">
                    <table>
                        <thead><tr><th>Row</th><th>Name in file</th><th>Why</th></tr></thead>
                        <tbody>
                            @foreach ($result['skipped'] as $skip)
                                <tr><td>{{ $skip['line'] }}</td><td>{{ $skip['name'] }}</td><td>{{ $skip['reason'] }}</td></tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="help">Every row was imported.</p>
            @endif
            <div class="actions">
                <a class="btn-tp" style="text-decoration:none" href="{{ route('transaction-portal.index') }}">Open the Transaction Portal</a>
            </div>
        </div>
    @endif

    @if ($step === 'upload')
        <div class="card-x">
            <h2>1. Upload the file</h2>
            <form method="POST" action="{{ route('transaction-portal.import.preview') }}" enctype="multipart/form-data">
                @csrf
                <div class="grid">
                    <div>
                        <label for="tpi-file">Spreadsheet (.xlsx, .xls or .csv)</label>
                        <input class="form-control @error('file') is-invalid @enderror" type="file" id="tpi-file" name="file" accept=".xlsx,.xls,.csv" required>
                        @error('file')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="tpi-date">Date, for rows without one</label>
                        <input class="form-control @error('date') is-invalid @enderror" type="date" id="tpi-date" name="date" value="{{ old('date', $today) }}" required>
                        @error('date')<div class="invalid-feedback d-block">{{ $message }}</div>@enderror
                    </div>
                    <div>
                        <label for="tpi-status">Status, for rows without one</label>
                        <select class="form-select" id="tpi-status" name="status">
                            <option value="Open" @selected(old('status', 'Open') === 'Open')>Open (still expected / unpaid)</option>
                            <option value="Closed" @selected(old('status') === 'Closed')>Closed (received / paid on that date)</option>
                        </select>
                    </div>
                    <div>
                        <button class="btn-tp" type="submit">Check the file</button>
                    </div>
                </div>
            </form>
            <p class="help">
                Columns, in this order when the file has no header row: <code>Project Name</code> <code>Payee</code> <code>Milestone</code> <code>Amount</code>,
                then optionally <code>Date</code> <code>Status</code> <code>Details</code>. With a header row the columns can be in any order, and a <code>Project Code</code> column may be used instead of the name.
                A name ending in <code>- Solar</code> or <code>- Storage</code> is the project, and that word is added to the milestone (<em>Milestone 1 - Storage</em>).
                A payee that does not exist yet is created.
                <a href="{{ route('transaction-portal.import.template') }}">Download a template</a>.
            </p>
        </div>
    @endif


    @if ($step === 'preview')
        @php
            $rowsC = collect($rows);
            $attention = $rowsC->filter(fn ($r) => ! empty($r['errors']) || ! $r['project_id'] || $r['match'] === 'partial');
            $dupes = $rowsC->filter(fn ($r) => $r['duplicate']);
            $ready = $rowsC->filter(fn ($r) => empty($r['errors']) && $r['project_id'] && $r['match'] !== 'partial' && ! $r['duplicate']);
            $newPayees = $rowsC->where('payee_new', true)->pluck('payee_label')->unique()->values();
        @endphp
        <datalist id="tpi-payees">
            @foreach ($payees as $label)
                <option value="{{ $label }}"></option>
            @endforeach
        </datalist>
        <form method="POST" action="{{ route('transaction-portal.import.store') }}" id="tpi-form" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div class="card-x">
                <h2>2. Check {{ $fileName }}</h2>
                <div class="stats">
                    <div class="stat"><span>Rows</span><b>{{ $rowsC->count() }}</b></div>
                    <div class="stat"><span>Ready</span><b>{{ $ready->count() }}</b></div>
                    <div class="stat"><span>Need attention</span><b>{{ $attention->count() }}</b></div>
                    <div class="stat"><span>Duplicates</span><b>{{ $dupes->count() }}</b></div>
                    <div class="stat"><span>Will be imported</span><b id="tpi-count">0</b></div>
                </div>
                <p class="help" style="margin-top:0">
                    Every value can be changed here before importing: fill in what the file is missing, and pick the project for a row
                    whose name was not found (the closest names are at the top of the list). Untick a row to leave it out.
                </p>
                @if ($newPayees->isNotEmpty())
                    <p class="help">New payees that will be created: <b>{{ $newPayees->implode(', ') }}</b>. They can be renamed or switched off later in Operations → Payees.</p>
                @endif
                @if ($dupes->isNotEmpty())
                    <div class="choice" style="margin-top:12px">
                        <span><b>Duplicates</b> (same project, payee, milestone and amount):</span>
                        <label><input type="radio" name="dupe_mode" value="skip" checked> Leave them out</label>
                        <label><input type="radio" name="dupe_mode" value="import"> Import them anyway</label>
                    </div>
                @endif
                <div class="alert alert-danger mt-3 mb-0" id="tpi-invalid" role="alert" hidden></div>
            </div>

            <div class="card-x table-wrap">
                <table class="tpi-edit">
                    <thead>
                        <tr>
                            <th><input type="checkbox" id="tpi-all" aria-label="Import every row" checked></th>
                            <th>Row</th><th>Name in file</th><th>Project</th><th>Payee</th><th>Milestone</th>
                            <th>Amount</th><th>Date</th><th>Status</th><th>Details</th><th>Check</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($rows as $row)
                            @php
                                $field = 'rows['.$row['line'].']';
                                $missing = implode(' ', $row['errors']);
                                $pick = ! $row['project_id'] || $row['match'] === 'partial';
                            @endphp
                            <tr data-row data-duplicate="{{ $row['duplicate'] ? 1 : 0 }}">
                                <td><input type="checkbox" class="tpi-include" name="{{ $field }}[include]" value="1" @checked(! $row['duplicate']) aria-label="Import row {{ $row['line'] }}"></td>
                                <td>{{ $row['line'] }}</td>
                                <td class="tpi-name">{{ $row['name'] }}@if ($row['code'])<div class="note">Code {{ $row['code'] }}</div>@endif</td>
                                <td class="tpi-project">
                                    @if (! $pick)
                                        <a href="{{ route('projects.show', $row['project_id']) }}" target="_blank" rel="noopener">{{ $row['project_name'] }}</a>
                                        <input type="hidden" name="{{ $field }}[project]" value="{{ $row['project_id'] }}" data-required>
                                    @else
                                        <select class="form-select select2 proj-select" name="{{ $field }}[project]" data-required aria-label="Project for row {{ $row['line'] }}">
                                            <option value="">Select project…</option>
                                            @if (! empty($row['candidates']) || $row['project_id'])
                                                <optgroup label="{{ $row['match'] === 'ambiguous' ? 'Possible matches' : 'Closest names' }}">
                                                    @if ($row['project_id'] && ! isset($row['candidates'][$row['project_id']]))
                                                        <option value="{{ $row['project_id'] }}" selected>{{ $row['project_name'] }}</option>
                                                    @endif
                                                    @foreach ($row['candidates'] as $id => $name)
                                                        <option value="{{ $id }}" @selected($row['project_id'] == $id)>{{ $name }}</option>
                                                    @endforeach
                                                </optgroup>
                                            @endif
                                            <optgroup label="All projects">
                                                @foreach ($projects as $id => $name)
                                                    <option value="{{ $id }}">{{ $name }}</option>
                                                @endforeach
                                            </optgroup>
                                        </select>
                                        <div class="note warn">
                                            @if ($row['match'] === 'ambiguous') More than one project has this name.
                                            @elseif ($row['match'] === 'partial') Closest name - check it.
                                            @else No project with this name{{ empty($row['candidates']) ? '.' : ' - closest names first.' }}
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td><input class="form-control" name="{{ $field }}[payee]" value="{{ $row['payee_label'] }}" list="tpi-payees" maxlength="100" data-required aria-label="Payee">
                                    @if ($row['payee_new'])<span class="chip new mt-1">New payee</span>@endif</td>
                                <td><input class="form-control" name="{{ $field }}[milestone]" value="{{ $row['milestone'] }}" maxlength="255" data-required aria-label="Milestone"></td>
                                <td><input class="form-control num" name="{{ $field }}[amount]" value="{{ $row['amount'] !== null ? number_format($row['amount'], 2, '.', '') : '' }}" inputmode="decimal" data-required data-amount aria-label="Amount"></td>
                                <td><input class="form-control" type="date" name="{{ $field }}[date]" value="{{ $row['date'] }}" data-required aria-label="Date"></td>
                                <td>
                                    <select class="form-select" name="{{ $field }}[status]" aria-label="Status">
                                        <option value="Open" @selected($row['status'] === 'Open')>Open</option>
                                        <option value="Closed" @selected($row['status'] === 'Closed')>Closed</option>
                                    </select>
                                </td>
                                <td><input class="form-control" name="{{ $field }}[details]" value="{{ $row['details'] }}" placeholder="Imported from file" aria-label="Details"></td>
                                <td class="tpi-check">
                                    @if ($missing)
                                        <span class="chip bad">Fill in</span><div class="note bad">{{ $missing }}</div>
                                    @elseif ($row['duplicate'])
                                        <span class="chip warn">Duplicate</span><div class="note warn">{{ $row['duplicate'] }}</div>
                                    @elseif ($pick)
                                        <span class="chip warn">Pick a project</span>
                                    @else
                                        <span class="chip ok">Ready</span>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="actions">
                <button class="btn-tp" type="submit">Import</button>
                <a class="btn-ghost" href="{{ route('transaction-portal.import') }}">Upload another file</a>
                <span class="help" style="margin:0">Nothing is saved until you press Import.</span>
            </div>
        </form>
    @endif
</div>
<style>
.tpi table.tpi-edit td { vertical-align: middle; }
.tpi table.tpi-edit .form-control, .tpi table.tpi-edit .form-select { min-height: 34px; padding: 4px 8px; font-size: 13px; min-width: 110px; }
.tpi table.tpi-edit input[type="date"] { min-width: 140px; }
.tpi table.tpi-edit .num { text-align: right; min-width: 100px; }
.tpi table.tpi-edit tr.is-out td { opacity: .45; }
.tpi table.tpi-edit .is-invalid, .tpi table.tpi-edit .is-invalid + .select2 .select2-selection { border-color: var(--bad) !important; background: #fff7f5; }
.tpi .tpi-project { min-width: 240px; }
.tpi .tpi-name { min-width: 150px; }
.tpi .tpi-project .select2-container { width: 100% !important; min-width: 220px; }
</style>
@endsection

{{-- After jQuery (the layout yields this last). --}}
@section('scripts')
<script>
(function () {
    var form = document.getElementById('tpi-form');
    if (!form) return;

    function rows() { return Array.prototype.slice.call(form.querySelectorAll('tr[data-row]')); }

    // A row left out sends nothing: its inputs are disabled.
    function setIncluded(tr, on) {
        tr.querySelector('.tpi-include').checked = on;
        tr.classList.toggle('is-out', !on);
        tr.querySelectorAll('input, select').forEach(function (el) {
            if (!el.classList.contains('tpi-include')) el.disabled = !on;
        });
        if (window.jQuery) jQuery(tr).find('select.select2').trigger('change.select2');
        count();
    }

    function count() {
        document.getElementById('tpi-count').textContent = rows().filter(function (tr) { return tr.querySelector('.tpi-include').checked; }).length;
    }

    function invalid(el) {
        if (el.disabled) return false;
        var v = (el.value || '').trim();
        if (el.hasAttribute('data-required') && v === '') return true;
        if (el.hasAttribute('data-amount')) {
            var n = Number(v.replace(/[$,\s]/g, '').replace(/^\((.*)\)$/, '-$1'));
            return !isFinite(n) || n === 0;
        }
        return false;
    }

    form.addEventListener('change', function (e) {
        if (e.target.classList.contains('tpi-include')) setIncluded(e.target.closest('tr'), e.target.checked);
        if (e.target.id === 'tpi-all') rows().forEach(function (tr) { setIncluded(tr, e.target.checked); });
        if (e.target.name === 'dupe_mode') {
            rows().filter(function (tr) { return tr.dataset.duplicate === '1'; })
                .forEach(function (tr) { setIncluded(tr, e.target.value === 'import'); });
        }
        if (e.target.matches('[data-required]') && !invalid(e.target)) e.target.classList.remove('is-invalid');
    });
    form.addEventListener('input', function (e) {
        if (e.target.matches('[data-required]') && !invalid(e.target)) e.target.classList.remove('is-invalid');
    });
    if (window.jQuery) jQuery(form).on('select2:select', 'select.proj-select', function () { this.classList.remove('is-invalid'); });

    form.addEventListener('submit', function (e) {
        var first = null, bad = 0;
        rows().forEach(function (tr) {
            if (!tr.querySelector('.tpi-include').checked) return;
            var rowBad = false;
            tr.querySelectorAll('[data-required]').forEach(function (el) {
                var isBad = invalid(el);
                el.classList.toggle('is-invalid', isBad);
                if (isBad) { rowBad = true; first = first || el; }
            });
            if (rowBad) bad++;
        });
        var box = document.getElementById('tpi-invalid');
        if (bad) {
            e.preventDefault();
            box.textContent = bad + (bad === 1 ? ' row is' : ' rows are') + ' missing a project or a value (marked in red). Fill them in, or untick those rows to leave them out.';
            box.hidden = false;
            (first.closest('td') || first).scrollIntoView({ behavior: 'smooth', block: 'center' });
            return;
        }
        box.hidden = true;

        // Send the kept rows as ONE field: a few hundred rows of separate inputs
        // would pass PHP's max_input_vars, which silently drops the rest.
        var payload = {};
        var rowField = /^rows\[(\d+)\]\[(\w+)\]$/;
        rows().forEach(function (tr) {
            if (!tr.querySelector('.tpi-include').checked) return;
            tr.querySelectorAll('[name]').forEach(function (el) {
                var m = el.name.match(rowField);
                if (!m) return;
                (payload[m[1]] = payload[m[1]] || {})[m[2]] = el.value;
            });
        });
        var hidden = document.createElement('input');
        hidden.type = 'hidden';
        hidden.name = 'payload';
        hidden.value = JSON.stringify(payload);
        form.appendChild(hidden);
        form.querySelectorAll('[name]').forEach(function (el) {
            if (rowField.test(el.name) || el.name === 'dupe_mode') el.disabled = true;
        });
        form.querySelector('button[type="submit"]').disabled = true;
    });

    rows().forEach(function (tr) { setIncluded(tr, tr.querySelector('.tpi-include').checked); });
})();
</script>
@endsection
