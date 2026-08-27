<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Deal Review. */
    private const DEAL_REVIEW_DEPARTMENT_ID = 1;

    /**
     * "Utility Bill Uploaded" sits beside Utility Company in the Deal Review
     * fields, and reads the opposite way round to MPU Required: "no" means the
     * bill still has to be collected, which starts a Utility Bill Follow Up;
     * the follow up clears when the bill is actually uploaded (answering "yes"
     * closes it too). Left NULL on every existing project on purpose - nothing
     * is back-filled, so no project inherits a follow up it never had.
     *
     * It is also registered as a Deal Review required field, so a project
     * cannot leave Deal Review until the question has been answered.
     */
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('utility_bill_required')->nullable()->after('utility_company');
        });

        DB::table('project_department_fields')->updateOrInsert(
            ['department_id' => self::DEAL_REVIEW_DEPARTMENT_ID, 'field_name' => 'utility_bill_required'],
            ['updated_at' => now(), 'created_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('project_department_fields')
            ->where('department_id', self::DEAL_REVIEW_DEPARTMENT_ID)
            ->where('field_name', 'utility_bill_required')
            ->delete();

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('utility_bill_required');
        });
    }
};
