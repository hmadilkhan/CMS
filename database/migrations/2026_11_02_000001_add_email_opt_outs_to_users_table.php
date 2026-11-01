<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Per-template email switches. `email_preference` stays the master switch;
 * `email_opt_outs` is the list of template keys a user has switched off.
 *
 * Users who had the old switch OFF start with every template off, so turning
 * the master back on does not suddenly start sending them everything. Users
 * who had it ON start with none opted out - every email stays enabled.
 */
return new class extends Migration
{
    /** The per-user templates as they exist today; fixed here on purpose. */
    private const TEMPLATE_KEYS = [
        'project_assigned',
        'project_date_updated',
        'note_mentioned',
        'email_received',
        'service_ticket_created',
        'service_ticket_resolved',
        'service_ticket_comment',
        'site_survey_scheduled',
        'acceptance_review_sent',
    ];

    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->json('email_opt_outs')->nullable()->after('email_preference');
        });

        DB::table('users')
            ->where('email_preference', 0)
            ->update(['email_opt_outs' => json_encode(self::TEMPLATE_KEYS)]);
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('email_opt_outs');
        });
    }
};
