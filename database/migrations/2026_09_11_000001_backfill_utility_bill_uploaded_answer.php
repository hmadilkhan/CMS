<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * "Utility Bill Uploaded" now answers itself once the bill is on the project
 * (DocumentFollowUpService::answerFieldFromDocument), but the projects whose
 * bill arrived before that was true still read "no" - the follow up is cleared,
 * the bill is filed under Utility Bills, and the Deal Review field contradicts
 * both. Set those straight once.
 *
 * Only projects that actually have a utility bill file are touched; a project
 * still owing its bill keeps its "no" and its open chase.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('projects')
            ->whereRaw('lower(utility_bill_required) = ?', ['no'])
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('project_files')
                    ->whereColumn('project_files.project_id', 'projects.id')
                    ->where('project_files.category', 'utility_bill')
                    ->whereNull('project_files.deleted_at');
            })
            ->update(['utility_bill_required' => 'yes']);
    }

    public function down(): void
    {
        // The previous value is not recoverable - and "yes" with the bill on
        // file is the true answer either way.
    }
};
