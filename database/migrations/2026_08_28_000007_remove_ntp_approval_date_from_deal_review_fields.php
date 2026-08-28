<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Deal Review. */
    private const DEAL_REVIEW_DEPARTMENT_ID = 1;

    private const FIELD = 'ntp_approval_date';

    /**
     * NTP Approval Date leaves the Deal Review department fields.
     *
     * The funding side owns the date now: it is collected in the Zones NTP tab
     * and is mandatory on the NTP -> M1 zone move (see docs/zones.md). Deal
     * Review therefore neither shows the field any more (the Livewire edit /
     * view field blades dropped it) nor may block a department move on it, so
     * its `project_department_fields` row - the row
     * `ProjectController::moveProject()` reads to build the required-field
     * check - is removed.
     *
     * The column itself stays: every project that already carries a date keeps
     * it, and the Permitting -> Installation gate still reads it.
     */
    public function up(): void
    {
        DB::table('project_department_fields')
            ->where('department_id', self::DEAL_REVIEW_DEPARTMENT_ID)
            ->where('field_name', self::FIELD)
            ->delete();
    }

    public function down(): void
    {
        DB::table('project_department_fields')->updateOrInsert(
            ['department_id' => self::DEAL_REVIEW_DEPARTMENT_ID, 'field_name' => self::FIELD],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }
};
