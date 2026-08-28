{{-- The Zones board, fetched into the projects page's Zones tab.

     Kanban: one column per zone side by side, the zone's project cards stacked
     down each column. Archived is not one of the columns - it is read through
     the Archived toggle, which re-fetches this same fragment. --}}
<style>
    /* Three tracks so the search box stays centred on the row while the archive
       toggle sits hard against the right edge. The middle track is elastic, so
       the field grows with the row instead of sitting at a fixed small width. */
    .zone-board-filters {
        display: grid;
        grid-template-columns: 1fr minmax(0, 900px) 1fr;
        align-items: center;
        gap: 0.75rem;
        margin-bottom: 1.5rem;
    }

    /* Fills its track: the board is a full-width workspace, so the field is not
       held back to the narrow width the Operational tab's box uses. */
    .zone-board-search {
        position: relative;
        grid-column: 2;
        width: 100%;
    }

    /* Squared off and lettered like the tab strips, so it belongs to the same
       set of controls instead of reading as a stray Bootstrap pill. */
    .zone-board-archive {
        grid-column: 3;
        justify-self: end;
        position: relative;
        display: inline-flex;
        align-items: center;
        min-height: 50px;
        padding: 0.6rem 1.15rem;
        border-radius: 12px !important;
        border: 1px solid rgba(240, 122, 36, 0.32) !important;
        box-shadow: 0 6px 18px -14px rgba(151, 76, 18, 0.55);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.7rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        white-space: nowrap;
    }

    /* The count rides the top-right corner, half on the button and half off it.
       The white ring is what makes that overlap read cleanly. */
    .zone-archive-count {
        position: absolute;
        top: 0;
        right: 0;
        transform: translate(38%, -38%);
        min-width: 24px;
        height: 24px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0 6px;
        border-radius: 999px;
        background: var(--solen-gradient);
        border: 2px solid #ffffff;
        color: #ffffff;
        font-size: 0.72rem;
        font-weight: 700;
        line-height: 1;
        letter-spacing: 0;
        box-shadow: 0 4px 10px -4px rgba(151, 76, 18, 0.65);
    }

    @media (max-width: 767px) {
        .zone-board-filters {
            grid-template-columns: 1fr;
            justify-items: center;
        }

        .zone-board-search,
        .zone-board-archive {
            grid-column: 1;
            justify-self: center;
        }
    }

    .zone-board-search i {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: #6c757d;
        font-size: 1.2rem;
        z-index: 5;
    }

    .zone-board-search input {
        padding: 12px 20px 12px 50px;
        height: 50px;
        border-radius: 999px;
        border: 1px solid var(--solen-primary-border-stronger);
        background: #ffffff;
        box-shadow: 0 14px 28px -24px rgba(52, 36, 22, 0.35);
        transition: all 0.3s ease;
        width: 100%;
    }

    .zone-board-search input:focus {
        border-color: var(--solen-primary);
        box-shadow: 0 0 0 4px var(--solen-primary-focus);
        outline: none;
    }

    .zone-board-search input::placeholder {
        color: #adb5bd;
        font-size: 0.9rem;
    }

    /* The department filter, drawn as the project page's department tab strip:
       one full-width segmented bar, equal segments, monospace labels. Same look
       as #departmentDetailTabs in show.blade.php, rebuilt here because those
       rules are scoped to that page's own wrapper. */
    .zone-department-tabs {
        display: flex;
        flex-wrap: nowrap;
        gap: 1px;
        width: 100%;
        margin: 0 0 1.5rem;
        padding: 1px;
        list-style: none;
        overflow: hidden;
        /* The gap colour IS the divider between segments, so it carries the
           strip's definition along with the outer border. */
        background: rgba(240, 122, 36, 0.32);
        border: 1px solid rgba(240, 122, 36, 0.32);
        border-radius: 14px;
        box-shadow: 0 6px 18px -14px rgba(151, 76, 18, 0.55);
    }

    .zone-department-tabs li {
        flex: 1 1 0;
        min-width: 0;
    }

    .zone-department-tabs button {
        width: 100%;
        min-height: 48px;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 0.6rem 0.35rem;
        border: 0;
        border-radius: 0;
        background: #ffffff;
        color: rgba(120, 53, 15, 0.72);
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.66rem;
        font-weight: 600;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        text-align: center;
        line-height: 1.25;
        transition: background 0.2s ease, color 0.2s ease;
    }

    .zone-department-tabs button:hover {
        background: var(--solen-primary-soft);
        color: var(--solen-warm-hover);
    }

    /* The active segment carries the brand gradient, same as the page's own
       Operational | Zones tabs - a tint was too faint to find at a glance. */
    .zone-department-tabs button.active {
        background: var(--solen-gradient);
        color: #ffffff;
        font-weight: 700;
    }

    /* `stretch` + a fixed column height keeps every lane the same size, however
       many cards it holds - an empty zone that collapsed to its header made the
       board look broken. */
    .zone-board {
        display: flex;
        align-items: stretch;
        gap: 1rem;
        overflow-x: auto;
        padding-bottom: 1rem;
    }

    .zone-column {
        flex: 1 0 320px;
        min-width: 320px;
        max-width: 420px;
        height: 72vh;
        min-height: 420px;
        /* A whisper of the brand orange, just enough to lift the column off the
           white page without four saturated bars shouting across the board. */
        background: rgba(240, 122, 36, 0.035);
        border: 1px solid var(--solen-primary-border);
        border-radius: 14px;
        display: flex;
        flex-direction: column;
    }

    .zone-column-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 0.5rem;
        padding: 0.85rem 1.1rem;
        background: var(--solen-primary-soft);
        color: var(--solen-warm-text);
        border-bottom: 1px solid var(--solen-primary-border);
        border-radius: 13px 13px 0 0;
        font-weight: 700;
        letter-spacing: 0.02em;
    }

    .zone-column-header i {
        color: var(--solen-primary-dark);
    }

    .zone-column-count {
        background: #ffffff;
        border: 1px solid var(--solen-primary-border);
        color: var(--solen-warm-text);
        border-radius: 999px;
        padding: 0.15rem 0.7rem;
        font-size: 0.82rem;
        font-weight: 700;
    }

    /* The cards stack down the column and scroll inside it, so the column
       headers stay put however long a lane gets. The scrollbar is styled rather
       than left as an overlay one, so a column with more cards below the fold
       says so instead of looking finished. */
    .zone-column-body {
        overflow-y: auto;
        padding: 0.75rem 0.4rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
        scrollbar-width: thin;
        scrollbar-color: rgba(240, 122, 36, 0.45) transparent;
    }

    .zone-column-body::-webkit-scrollbar {
        width: 8px;
    }

    .zone-column-body::-webkit-scrollbar-track {
        background: transparent;
    }

    .zone-column-body::-webkit-scrollbar-thumb {
        background: rgba(240, 122, 36, 0.38);
        border-radius: 999px;
    }

    .zone-column-body::-webkit-scrollbar-thumb:hover {
        background: rgba(240, 122, 36, 0.6);
    }

    .zone-card-wrap .project-card {
        margin: 0 0.4rem;
    }

    .zone-card-action {
        margin: 0.4rem 0.4rem 0;
    }

    .zone-board .zone-card-action .btn {
        padding: 0.25rem 0.5rem;
        font-size: 0.78rem;
    }

    /* Bootstrap themes `.btn-primary` for us but not `.btn-outline-*`, so an
       untouched outline button lands on the board as Bootstrap blue. Both
       outline buttons here are given the warm palette by hand. */
    .zone-board .zone-card-action .btn,
    .zone-board-filters .btn {
        background: #ffffff;
        border: 1px solid var(--solen-primary-border-stronger);
        color: var(--solen-warm-text);
        font-weight: 600;
    }

    .zone-board .zone-card-action .btn:hover,
    .zone-board-filters .btn:hover {
        background: var(--solen-primary-soft);
        border-color: var(--solen-primary);
        color: var(--solen-warm-hover);
    }

    .zone-column-empty {
        padding: 2rem 1rem;
        text-align: center;
        color: #6c757d;
        font-style: italic;
    }

    /* Card styling lifted from the projects page so both read as one design. */
    .zone-board .project-card {
        transition: all 0.3s ease;
        border: 1px solid var(--solen-primary-border-stronger) !important;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
    }

    .zone-board .project-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.15);
    }

    /* Compact: the board shows several cards per column, so every card is
       trimmed against the projects page's roomier one. Same parts, less air -
       a tall card hid the next project below the fold entirely. */
    .zone-board .project-header {
        background: #ffffff;
        border-bottom: 1px solid var(--solen-primary-border);
        padding: 0.6rem 0.75rem;
        color: var(--solen-warm-text);
    }

    .zone-board .card-body {
        padding: 0.6rem 0.75rem 0.75rem;
    }

    .zone-board .zone-card-title {
        font-size: 0.95rem;
        line-height: 1.2;
        max-width: 165px;
    }

    .zone-board .zone-card-code {
        display: inline-block;
        font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace;
        font-size: 0.72rem;
        font-weight: 600;
        color: var(--solen-primary-dark);
        opacity: 0.85;
    }

    .zone-board .zone-card-progress {
        margin-top: 0.6rem;
    }

    .zone-board .project-header .text-white {
        color: var(--solen-warm-text) !important;
    }

    .zone-board .project-header img {
        border-color: var(--solen-primary-border-stronger) !important;
    }

    .zone-board .days-badge {
        background: rgba(245, 158, 11, 0.1);
        color: var(--solen-warm-text);
        padding: 0.25rem 0.6rem;
        border-radius: 20px;
        font-size: 0.8rem;
        font-weight: 600;
    }

    .zone-board .project-header .icofont-email {
        color: var(--solen-primary-dark) !important;
    }

    .zone-board .info-row {
        padding: 0.28rem 0;
        border-bottom: 1px solid #f4f4f4;
    }

    .zone-board .info-row:last-of-type {
        border-bottom: none;
    }

    .zone-board .info-label {
        color: #6c757d;
        font-size: 0.78rem;
        font-weight: 500;
    }

    .zone-board .info-label i {
        margin-right: 0.35rem !important;
    }

    .zone-board .info-value {
        font-size: 0.82rem;
        font-weight: 600;
    }

    .zone-board .badge {
        font-size: 0.7rem;
        padding: 0.25em 0.55em;
    }

    .zone-board .badge-not-initiated {
        background-color: #1d4ed8 !important;
        color: #ffffff !important;
    }

    .zone-board .progress-modern {
        height: 6px;
        border-radius: 10px;
        background: #e9ecef;
    }

    .zone-board .progress-modern .progress-bar {
        border-radius: 10px;
        background: var(--solen-gradient-horizontal);
    }

    .zone-board .notes-section {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 0.4rem 0.6rem;
        margin-top: 0.6rem;
        font-size: 0.78rem;
        color: #495057;
    }

    .zone-board .project-link {
        display: block;
        color: inherit;
        text-decoration: none;
    }

    .zone-board .project-link:hover,
    .zone-board .project-link:focus {
        color: inherit;
        text-decoration: none;
    }
</style>

<div class="zone-board-filters">
    <div class="zone-board-search">
        <i class="icofont-search"></i>
        <input type="text" id="zoneBoardSearch" value="{{ $search }}"
            placeholder="Search by project, code or customer" />
    </div>
    @if ($isArchiveView)
        <button type="button" class="btn zone-board-archive" data-zone-board-archived="0">
            <i class="icofont-rounded-left me-2"></i>Back to Zones
        </button>
    @else
        <button type="button" class="btn zone-board-archive" data-zone-board-archived="1">
            <i class="icofont-archive me-2"></i>Archived
            @if ($archivedCount)
                <span class="zone-archive-count">{{ $archivedCount }}</span>
            @endif
        </button>
    @endif
</div>

{{-- Department filter, across the full width like the project page's own
     department tabs. --}}
<ul class="zone-department-tabs" id="zoneBoardDepartments">
    <li>
        <button type="button" data-zone-department="all"
            class="{{ (string) $departmentFilter === 'all' || $departmentFilter === null || $departmentFilter === '' ? 'active' : '' }}">All</button>
    </li>
    @foreach ($departments as $department)
        <li>
            <button type="button" data-zone-department="{{ $department->id }}"
                class="{{ (string) $departmentFilter === (string) $department->id ? 'active' : '' }}">{{ $department->name }}</button>
        </li>
    @endforeach
</ul>

<div class="zone-board">
    @foreach ($lanes as $lane)
        @php $projects = $projectsByZone->get($lane->id, collect()); @endphp
        <div class="zone-column">
            <div class="zone-column-header">
                <span><i class="icofont-layers me-2"></i>{{ $lane->name }}</span>
                <span class="zone-column-count">{{ $projects->count() }}</span>
            </div>
            <div class="zone-column-body">
                @forelse ($projects as $project)
                    @include('zones.partials.project-card', ['project' => $project])
                @empty
                    <div class="zone-column-empty">No projects in this zone.</div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>
