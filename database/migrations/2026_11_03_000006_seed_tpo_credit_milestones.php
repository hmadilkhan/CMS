<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The four TPO credit milestones, on the two finance options that split the
 * contract (Prepaid PPA, Wheelhouse Credit Union - see
 * FinanceOption::usesCustomerPortion): 70% of each credit when the M1 Approval
 * Date is filed, 30% at M2. Paid by the third party, so the payee is Others.
 *
 * Seeded once; after this they are ordinary rows the user edits. A row whose
 * key already exists is left alone, so running it twice adds nothing.
 */
return new class extends Migration
{
    private const KEYS = ['solar_tpo_m1', 'solar_tpo_m2', 'battery_tpo_m1', 'battery_tpo_m2'];

    public function up(): void
    {
        $m1 = DB::table('zones')->where('slug', 'm1')->value('id');
        $m2 = DB::table('zones')->where('slug', 'm2')->value('id');

        if (! $m1 || ! $m2) {
            return;
        }

        $rows = [
            ['solar_tpo_m1', 'Solar Milestone 1', $m1, 70, 'solar_tpo_credit'],
            ['solar_tpo_m2', 'Solar Milestone 2', $m2, 30, 'solar_tpo_credit'],
            ['battery_tpo_m1', 'Battery Milestone 1', $m1, 70, 'battery_tpo_credit'],
            ['battery_tpo_m2', 'Battery Milestone 2', $m2, 30, 'battery_tpo_credit'],
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

            foreach ($rows as [$key, $label, $zoneId, $percent, $base]) {
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
                    'amount_base' => $base,
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
