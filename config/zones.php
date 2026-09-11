<?php

/**
 * The Zones module. Zones are a second, funding-side pipeline that runs beside
 * the department pipeline without touching it - see docs/zones.md.
 *
 * Only two moves happen on their own; every other zone change is the Funding
 * Manager's manual decision and is never overridden by a department move.
 */
return [
    /*
     * The one department that pulls a project into the module. A project
     * landing in Deal Review with no zone yet enters at Pre NTP.
     */
    'entry' => [
        'department_id' => 1,   // Deal Review
        'zone' => 'pre_ntp',
    ],

    /*
     * The one automatic promotion. It fires only while the project is still in
     * `from_zone`: once the Funding Manager has moved it on to M1/M2, a
     * department move can never pull the zone backwards.
     */
    'promotion' => [
        'department_id' => 2,   // Site Survey
        'from_zone' => 'pre_ntp',
        'to_zone' => 'ntp',
    ],

    /*
     * A paperwork chase whose document the FUNDING side owns moves the project
     * into that zone when it parks, so the tab that collects the field is the
     * project's own tab and the Funding Manager finds it on the right lane.
     *
     * One-directional, like the promotion above: a project the Funding Manager
     * has already pushed further along is left exactly where it is.
     */
    'follow_up_zones' => [
        'ntp_approval' => 'ntp',
    ],

    /*
     * Fields the funding side fills in from a zone's own tab, keyed by zone
     * slug and then by the `projects` column they write. A field listed here
     * belongs to the funding side alone - keep it out of the department field
     * panels, so one owner writes it. The tab only offers them while the
     * project is actually in that zone (ZoneController::fields enforces it).
     */
    'zone_fields' => [
        'ntp' => [
            'ntp_approval_date' => [
                'label' => 'NTP Approval Date',
                'type' => 'date',
            ],
        ],
    ],

    /*
     * A one-off catch-up: where a project that is already down the pipeline
     * belongs on the funding board, read from the department it sits in.
     *
     * This is NOT how projects get their zone from now on - the two rules
     * above are, and they are unchanged. It exists only so `zones:backfill`
     * can place the existing backlog once, and it never touches a project the
     * Funding Manager has already put somewhere.
     */
    'backfill' => [
        'departments' => [
            1 => 'pre_ntp',   // Deal Review
            2 => 'ntp',       // Site Survey        - before installation
            3 => 'ntp',       // Engineering        - before installation
            4 => 'ntp',       // Permitting         - before installation
            5 => 'm1',        // Installation
            6 => 'm1',        // Inspection
            7 => 'm2',        // PTO                - after inspection
            8 => 'm2',        // Certificate of Completion
        ],

        /*
         * Departments the catch-up leaves alone. 9 is the operations Archive
         * lane: "active" means everything not sitting in it.
         */
        'skip_departments' => [9],
    ],

    /*
     * The lane that is kept off the board. It stays a valid move destination and
     * is read back through the board's Archived view.
     */
    'archived_zone' => 'archived',

    /*
     * The role the module is built for. A user holding only this role has no
     * Operations side at all, so they land on the board and see nothing else.
     */
    'role' => 'Funding Manager',
];
