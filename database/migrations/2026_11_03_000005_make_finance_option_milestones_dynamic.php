<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Finance option milestones become rows the user edits, not four hard-coded
 * ones. Each row now carries what used to be decided for the whole finance
 * option or not at all:
 *
 *   - amount_base:     what a percent / remaining is a share of (it was the
 *                      option's `milestone_amount_source`, forced to Customer
 *                      Portion for Prepaid PPA);
 *   - payee:           who the account transaction is for (always customer);
 *   - trigger_zone_id: the zone a `zone_date` / `zone_entered` trigger watches.
 *
 * Every existing row and event is given the base it was actually paid on, so
 * "remaining" keeps adding up exactly as before. `finance_options.milestone_amount_source`
 * is left in place but no longer read.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_option_milestones', function (Blueprint $table) {
            $table->string('amount_base')->default('contract_amount')->after('amount_value');
            $table->string('payee')->default('customer')->after('amount_base');
            $table->unsignedBigInteger('trigger_zone_id')->nullable()->after('trigger_field');
            $table->foreign('trigger_zone_id', 'fom_trigger_zone_fk')->references('id')->on('zones')->nullOnDelete();
        });

        Schema::table('project_finance_milestone_events', function (Blueprint $table) {
            $table->string('amount_base')->default('contract_amount')->after('key');
        });

        $bases = DB::table('finance_options')->get(['id', 'name', 'milestone_amount_source'])
            ->mapWithKeys(fn ($option) => [
                $option->id => strcasecmp(trim((string) $option->name), 'Prepaid PPA') === 0
                    ? 'customer_portion'
                    : ($option->milestone_amount_source ?: 'contract_amount'),
            ]);

        foreach ($bases as $financeOptionId => $base) {
            DB::table('finance_option_milestones')
                ->where('finance_option_id', $financeOptionId)
                ->update(['amount_base' => $base]);
        }

        $milestoneBases = DB::table('finance_option_milestones')->pluck('amount_base', 'id');

        DB::table('project_finance_milestone_events')
            ->select('id', 'finance_option_milestone_id')
            ->orderBy('id')
            ->each(function ($event) use ($milestoneBases) {
                DB::table('project_finance_milestone_events')
                    ->where('id', $event->id)
                    ->update(['amount_base' => $milestoneBases[$event->finance_option_milestone_id] ?? 'contract_amount']);
            });
    }

    public function down(): void
    {
        Schema::table('project_finance_milestone_events', function (Blueprint $table) {
            $table->dropColumn('amount_base');
        });

        Schema::table('finance_option_milestones', function (Blueprint $table) {
            $table->dropForeign('fom_trigger_zone_fk');
            $table->dropColumn(['amount_base', 'payee', 'trigger_zone_id']);
        });
    }
};
