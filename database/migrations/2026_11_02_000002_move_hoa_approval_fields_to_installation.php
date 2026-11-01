<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /** Permitting. */
    private const PERMITTING_DEPARTMENT_ID = 4;

    /** Installation. */
    private const INSTALLATION_DEPARTMENT_ID = 5;

    private const FIELDS = ['hoa_approval_request_date', 'hoa_approval_date'];

    /**
     * The two HOA approval dates move from Permitting to Installation.
     *
     * Their `project_department_fields` rows are what
     * `ProjectController::moveProject()` reads to build the required-field
     * check, so re-pointing them makes Permitting stop blocking on them and
     * Installation (when the project has HOA = yes) block the move to
     * Inspection instead. The columns on `projects` are untouched: every date
     * already filed stays where it is.
     */
    public function up(): void
    {
        $this->repoint(self::PERMITTING_DEPARTMENT_ID, self::INSTALLATION_DEPARTMENT_ID);
    }

    public function down(): void
    {
        $this->repoint(self::INSTALLATION_DEPARTMENT_ID, self::PERMITTING_DEPARTMENT_ID);
    }

    private function repoint(int $from, int $to): void
    {
        foreach (self::FIELDS as $field) {
            $moved = DB::table('project_department_fields')
                ->where('department_id', $from)
                ->where('field_name', $field)
                ->update(['department_id' => $to, 'updated_at' => now()]);

            if (! $moved) {
                DB::table('project_department_fields')->updateOrInsert(
                    ['department_id' => $to, 'field_name' => $field],
                    ['created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }
};
