<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * The TPO credit seed (2026_11_03_000006) assumed Prepaid PPA / Wheelhouse
 * already had the standard four milestones - true wherever the option had
 * been saved since milestones existed, because the old code created them on
 * every save. Where it never had been (staging), the option was left with the
 * four TPO rows alone, and because it now "has rows" the editor would never
 * offer the standard four again.
 *
 * Give any such option the standard four ahead of its TPO rows. An option that
 * already has (or once had) any of them is left alone, so this does nothing on
 * a database where the defaults were already there.
 */
return new class extends Migration
{
    private const TPO_KEYS = ['solar_tpo_m1', 'solar_tpo_m2', 'battery_tpo_m1', 'battery_tpo_m2'];

    private const DEFAULT_KEYS = ['deal_review', 'permit_approval', 'solar_install', 'inspection_approval'];

    public function up(): void
    {
        $optionIds = DB::table('finance_option_milestones')
            ->whereIn('key', self::TPO_KEYS)
            ->distinct()
            ->pluck('finance_option_id');

        $now = now();

        foreach ($optionIds as $financeOptionId) {
            $hasDefaults = DB::table('finance_option_milestones')
                ->where('finance_option_id', $financeOptionId)
                ->whereIn('key', self::DEFAULT_KEYS)
                ->exists();

            if ($hasDefaults) {
                continue;
            }

            $base = $this->baseFor($financeOptionId);

            DB::table('finance_option_milestones')
                ->where('finance_option_id', $financeOptionId)
                ->update(['sort_order' => DB::raw('sort_order + 4')]);

            DB::table('finance_option_milestones')->insert([
                $this->row($financeOptionId, 'deal_review', 'Deal Review', 'project_created', null, 'fixed', 1000, $base, 1, $now),
                $this->row($financeOptionId, 'permit_approval', 'Permit Approval Date', 'project_date', 'permitting_approval_date', 'percent', 50, $base, 2, $now),
                $this->row($financeOptionId, 'solar_install', 'Solar Install Date', 'project_date', 'solar_install_date', 'percent', 35, $base, 3, $now),
                $this->row($financeOptionId, 'inspection_approval', 'Inspection Approval Date', 'project_date', 'inspection_approval_date', 'remaining', null, $base, 4, $now),
            ]);
        }
    }

    public function down(): void
    {
        // Not reversed: the rows are indistinguishable from ones the editor
        // made, and a fired event may already point at them.
    }

    /** The customer-paid base the option's old "Amount Source" chose. */
    private function baseFor(int $financeOptionId): string
    {
        $option = DB::table('finance_options')->where('id', $financeOptionId)->first(['name', 'milestone_amount_source']);

        if (! $option) {
            return 'contract_amount';
        }

        return strcasecmp(trim((string) $option->name), 'Prepaid PPA') === 0
            ? 'customer_portion'
            : ($option->milestone_amount_source ?: 'contract_amount');
    }

    private function row(int $financeOptionId, string $key, string $label, string $triggerType, ?string $triggerField, string $amountType, $amountValue, string $base, int $sortOrder, $now): array
    {
        return [
            'finance_option_id' => $financeOptionId,
            'key' => $key,
            'label' => $label,
            'trigger_type' => $triggerType,
            'trigger_field' => $triggerField,
            'trigger_zone_id' => null,
            'amount_type' => $amountType,
            'amount_value' => $amountValue,
            'amount_base' => $base,
            'payee' => 'customer',
            'sort_order' => $sortOrder,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ];
    }
};
