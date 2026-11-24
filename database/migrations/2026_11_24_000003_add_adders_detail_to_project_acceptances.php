<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The price of each adder on the acceptance page ("Tesla Expansion Battery
 * ($8,250.00), ..."). The snapshot only kept the names (`adders_list`), so this
 * adds `adders_detail` (name + amount) and recovers it for existing acceptances.
 *
 * Not from today's adders: a deal's adders change after a review is sent, and
 * the page shows the deal as it was then. Customer adders are never edited in
 * place - every change soft deletes the row and creates a new one - so the
 * adders live at a moment are the rows created by then and not yet deleted.
 *
 * Tried at the acceptance's created_at (when the review was sent) and then at
 * its updated_at (acceptances from before 2025-12-09 had their snapshot filled
 * in later by acceptance:backfill-financials, from the deal as it was then). A
 * moment is used only when its adders give exactly the snapshot's names AND its
 * Adder Total, so a price never disagrees with the figures already on the page.
 * No match = no detail, and the page keeps showing the names alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_acceptances', function (Blueprint $table) {
            $table->json('adders_detail')->nullable()->after('adders_list');
        });

        DB::table('project_acceptances')
            ->whereNull('adders_detail')
            ->orderBy('id')
            ->select(['id', 'project_id', 'created_at', 'updated_at', 'adders_list', 'adders_amount'])
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
        Schema::table('project_acceptances', function (Blueprint $table) {
            $table->dropColumn('adders_detail');
        });
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
                ->get(['adder_types.name', 'customer_adders.amount']);

            $rowNames = $rows->pluck('name')->filter()->values()->all();
            sort($rowNames);

            if ($rowNames === $names && abs($rows->sum('amount') - (float) $acceptance->adders_amount) < 0.01) {
                return $rows->filter(fn ($row) => ! empty($row->name))
                    ->map(fn ($row) => ['name' => $row->name, 'amount' => round((float) $row->amount, 2)])
                    ->values()
                    ->all();
            }
        }

        return null;
    }
};
