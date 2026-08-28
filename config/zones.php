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
