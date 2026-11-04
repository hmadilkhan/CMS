<?php

/**
 * Transaction Portal (Finance -> Transaction Portal). See docs/transaction-portal.md.
 */
return [
    /*
     * A material invoice is due this many days after its invoice date.
     */
    'material_due_days' => 30,

    /*
     * The portal's week runs Sunday to Saturday (Carbon day number, 0 = Sunday).
     * The commission payable is due on the Friday of the week AFTER the one the
     * payment arrived in, so this also decides which Friday that is.
     */
    'week_starts_on' => 0,
];
