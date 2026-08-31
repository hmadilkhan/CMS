<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Permitting. */
    private const PERMITTING_DEPARTMENT_ID = 4;

    /**
     * Sets up the Fire Review Follow Up.
     *
     * 1. fire_review_required becomes nullable. It was NOT NULL DEFAULT 0, so
     *    "not answered yet" and "no fire review needed" were the same value -
     *    and the field cannot be made a Permitting requirement while 0 doubles
     *    as unanswered. Existing 0/1 answers are left exactly as they are; only
     *    new projects start out NULL.
     *
     * 2. Registers it as a Permitting required field, so a project cannot leave
     *    Permitting until the question has been answered.
     *
     * 3. Marks every project that already answered "yes" as pre-existing, so the
     *    chase does not open for work that predates this feature. Without this,
     *    83 projects would appear on the Permitting dashboard at once, each one
     *    stuck open until somebody dug out a fire approval document for it. To
     *    let a chase open for one of these projects after all, delete its
     *    pre_existing row.
     */
    public function up(): void
    {
        // MODIFY is MySQL-only syntax and fails on SQLite, which the AI chat tests
        // run on; the column keeps whatever nullability the create migration gave
        // it there.
        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE projects MODIFY fire_review_required TINYINT(1) NULL DEFAULT NULL');
        }

        DB::table('project_department_fields')->updateOrInsert(
            ['department_id' => self::PERMITTING_DEPARTMENT_ID, 'field_name' => 'fire_review_required'],
            ['updated_at' => now(), 'created_at' => now()]
        );

        $now = now();

        $rows = DB::table('projects')
            ->whereNull('deleted_at')
            ->where('fire_review_required', 1)
            ->pluck('id')
            ->map(fn ($projectId) => [
                'project_id' => $projectId,
                'type' => 'fire_review',
                'status' => 'Resolved',
                'opened_at' => $now,
                'resolved_at' => $now,
                'resolved_reason' => 'pre_existing',
                'created_at' => $now,
                'updated_at' => $now,
            ])
            ->all();

        foreach (array_chunk($rows, 200) as $chunk) {
            DB::table('project_document_follow_ups')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('project_document_follow_ups')
            ->where('type', 'fire_review')
            ->where('resolved_reason', 'pre_existing')
            ->delete();

        DB::table('project_department_fields')
            ->where('department_id', self::PERMITTING_DEPARTMENT_ID)
            ->where('field_name', 'fire_review_required')
            ->delete();

        DB::statement('UPDATE projects SET fire_review_required = 0 WHERE fire_review_required IS NULL');

        if (DB::getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE projects MODIFY fire_review_required TINYINT(1) NOT NULL DEFAULT 0');
        }
    }
};
