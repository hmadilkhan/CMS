<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The TPO payments for customers saved BEFORE the Solar / Battery split, who
 * carry one Third Party Credit: 70% of it when the M1 Approval Date is filed,
 * 30% at M2, payee Others - the same schedule, on the same two finance
 * options, as the Solar / Battery rows.
 *
 * They sit beside those rows and never overlap them: their base
 * (single_third_party_credit) is 0 on a split customer, and theirs is 0 on a
 * pre-split one (FinanceMilestoneService::baseAmount), so each project raises
 * one set or the other. Nothing is backfilled: projects whose M1 / M2 date is
 * already filed raise nothing for it.
 *
 * Seeded once; after this they are ordinary rows the user edits. A row whose
 * key already exists is left alone, so running it twice adds nothing.
 */
return new class extends Migration
{
    private const KEYS = ['third_party_credit_m1', 'third_party_credit_m2'];

    public function up(): void
    {
        $m1 = DB::table('zones')->where('slug', 'm1')->value('id');
        $m2 = DB::table('zones')->where('slug', 'm2')->value('id');

        if (! $m1 || ! $m2) {
            return;
        }

        $rows = [
            ['third_party_credit_m1', 'Third Party Credit Milestone 1', $m1, 70],
            ['third_party_credit_m2', 'Third Party Credit Milestone 2', $m2, 30],
        ];

        $options = DB::table('finance_options')
            ->whereNull('deleted_at')
            ->where(function ($query) {
                $query->whereIn('id', [9, 10])
                    ->orWhereIn('name', ['Prepaid PPA', 'Wheelhouse Credit Union']);
            })
            ->pluck('id');

        $now = now();

        foreach ($options as $financeOptionId) {
            $sortOrder = (int) DB::table('finance_option_milestones')
                ->where('finance_option_id', $financeOptionId)
                ->max('sort_order');

            foreach ($rows as [$key, $label, $zoneId, $percent]) {
                $exists = DB::table('finance_option_milestones')
                    ->where('finance_option_id', $financeOptionId)
                    ->where('key', $key)
                    ->exists();

                if ($exists) {
                    continue;
                }

                DB::table('finance_option_milestones')->insert([
                    'finance_option_id' => $financeOptionId,
                    'key' => $key,
                    'label' => $label,
                    'trigger_type' => 'zone_date',
                    'trigger_field' => null,
                    'trigger_zone_id' => $zoneId,
                    'amount_type' => 'percent',
                    'amount_value' => $percent,
                    'amount_base' => 'single_third_party_credit',
                    'payee' => 'others',
                    'sort_order' => ++$sortOrder,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        DB::table('finance_option_milestones')->whereIn('key', self::KEYS)->delete();
    }
};
