<?php

/**
 * Finance option milestones - the payments a finance option raises as a
 * project moves along. The rows themselves are data (Operations -> Finance
 * Options); this file only lists what a row may point at.
 *
 * Zone triggers are NOT listed here: every zone with `has_approval_date` offers
 * "<Zone> Approval Date", and every zone but the archive offers "Moved into
 * <Zone>", straight from the `zones` table. Adding a zone adds its triggers.
 */
return [
    /*
     * The `projects` date columns a milestone may fire on, with the label the
     * trigger dropdown shows. A column is only ever read here, never written,
     * so adding one is safe - but it must be a real date column that some form
     * saves, or the milestone can never fire.
     */
    'project_dates' => [
        'ntp_approval_date' => 'NTP Approval Date',
        'permitting_approval_date' => 'Permitting Approval Date',
        'solar_install_date' => 'Solar Install Date',
        'inspection_approval_date' => 'Inspection Approval Date',
        'pto_approval_date' => 'PTO Approval Date',
    ],

    /*
     * The `customer_finances` figures a percent / remaining milestone is a share
     * of. "Remaining" is the base minus what earlier milestones on the SAME base
     * already raised, so a TPO credit payment never eats into the customer's.
     */
    'amount_bases' => [
        'contract_amount' => 'Contract Amount',
        'customer_portion' => 'Customer Portion',
        'solar_tpo_credit' => 'Solar TPO Credit',
        'battery_tpo_credit' => 'Battery TPO Credit',
    ],
];
