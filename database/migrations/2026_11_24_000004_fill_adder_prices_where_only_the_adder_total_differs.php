<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Follows 2026_11_24_000003, which recovered each adder's price only when the
 * adders at the review gave both the snapshot's names AND its Adder Total.
 *
 * For a few acceptances the names match exactly but the total does not: the
 * page's Adder Total is the deal's customer_finances.adders, which on those
 * deals had drifted from the adder rows (e.g. $19,300 against rows adding up to
 * $17,500). The rows are still the adders the review carried, so their prices
 * are shown - decided with the business, knowing they will not add up to the
 * Adder Total on those pages. Five acceptances on production at the time.
 *
 * Acceptances whose names never match are left with the names alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('project_acceptances')
            ->whereNull('adders_detail')
            ->orderBy('id')
            ->select(['id', 'project_id', 'created_at', 'updated_at', 'adders_list'])
            ->chunkById(200, function ($acceptances) {
                foreach ($acceptances as $acceptance) {
                    $detail = $this->recover($acceptance);
                    if ($detail !== null) {
                        DB::table('project_acceptances')
                            ->where('id', $acceptance->id)
                            ->update(['adders_detail' => json_encode($detail)]);
                    }
                }
            });
    }

    public function down(): void
    {
        // Nothing to undo: 000003's down() drops the column itself.
    }

    private function recover(object $acceptance): ?array
    {
        $names = json_decode((string) $acceptance->adders_list, true);
        $customerId = DB::table('projects')->where('id', $acceptance->project_id)->value('customer_id');

        if (! is_array($names) || $names === [] || empty($customerId)) {
            return null;
        }

        sort($names);

        foreach (array_filter([$acceptance->created_at, $acceptance->updated_at]) as $moment) {
            $rows = DB::table('customer_adders')
                ->leftJoin('adder_types', 'adder_types.id', '=', 'customer_adders.adder_type_id')
                ->where('customer_adders.customer_id', $customerId)
                ->where('customer_adders.created_at', '<=', $moment)
                ->where(fn ($q) => $q->whereNull('customer_adders.deleted_at')->orWhere('customer_adders.deleted_at', '>', $moment))
                ->orderBy('customer_adders.id')
                ->get(['adder_types.name', 'customer_adders.amount'])
                ->filter(fn ($row) => ! empty($row->name))
                ->values();

            $rowNames = $rows->pluck('name')->all();
            sort($rowNames);

            if ($rowNames === $names) {
                return $rows->map(fn ($row) => ['name' => $row->name, 'amount' => round((float) $row->amount, 2)])->all();
            }
        }

        return null;
    }
};
