<?php

namespace App\Console\Commands;

use App\Support\PhoneNumber;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Rewrites customers.phone as "(123) 123-9090".
 *
 * Soft-deleted rows are included. A value that does not carry ten digits
 * (optionally behind a leading 1) is left exactly as it is and listed, so
 * nothing is guessed at - those need a person to correct them. Safe to run
 * again: a phone already in the format is not touched.
 */
class FormatCustomerPhones extends Command
{
    protected $signature = 'customers:format-phones {--dry-run : Report what would change without writing}';

    protected $description = 'Rewrite customer phone numbers as (123) 123-9090';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $changed = 0;
        $already = 0;
        $skipped = [];

        DB::table('customers')
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->orderBy('id')
            ->select(['id', 'phone', 'deleted_at'])
            ->chunkById(200, function ($customers) use ($dryRun, &$changed, &$already, &$skipped) {
                foreach ($customers as $customer) {
                    $formatted = PhoneNumber::format($customer->phone);

                    if ($formatted === null) {
                        $skipped[] = [$customer->id, $customer->phone, $customer->deleted_at ? 'deleted' : 'active'];

                        continue;
                    }

                    if ($formatted === $customer->phone) {
                        $already++;

                        continue;
                    }

                    if (! $dryRun) {
                        DB::table('customers')->where('id', $customer->id)->update(['phone' => $formatted]);
                    }

                    $changed++;
                }
            });

        $this->info(($dryRun ? 'Would change ' : 'Changed ').$changed." phone(s); {$already} already in the format.");

        if ($skipped !== []) {
            $this->warn(count($skipped).' phone(s) are not 10-digit US numbers and were left as they are:');
            $this->table(['Customer ID', 'Phone', 'State'], $skipped);
        }

        return self::SUCCESS;
    }
}
