<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds pure-observability profiling columns to ai_query_logs (Phase 0).
 *
 * All columns are nullable / defaulted, so existing rows and behaviour are
 * unaffected. Populated only when config('ai.profiling.enabled') is true.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ai_query_logs', function (Blueprint $table) {
            $table->unsignedTinyInteger('openai_calls')->nullable()->after('total_tokens');
            $table->unsignedInteger('openai_ms')->nullable()->after('openai_calls');
            $table->unsignedInteger('db_ms')->nullable()->after('openai_ms');
            $table->string('engine', 32)->nullable()->index()->after('db_ms');
            $table->unsignedTinyInteger('fallbacks')->default(0)->after('engine');
            $table->json('stage_timings')->nullable()->after('fallbacks');
            $table->char('question_hash', 32)->nullable()->index()->after('stage_timings');
        });
    }

    public function down(): void
    {
        Schema::table('ai_query_logs', function (Blueprint $table) {
            $table->dropColumn([
                'openai_calls',
                'openai_ms',
                'db_ms',
                'engine',
                'fallbacks',
                'stage_timings',
                'question_hash',
            ]);
        });
    }
};
