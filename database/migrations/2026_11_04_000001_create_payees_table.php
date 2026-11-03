<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The payee list, which used to be a constant (AccountTransaction::PAYEES).
 *
 * The five built-in payees are seeded as `is_system` rows under their old keys,
 * so every stored `account_transactions.payee` and milestone payee keeps its
 * meaning. Users add their own from the milestone editor; those rows are not
 * system rows. `payee` columns keep storing the key, as before.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payees', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('label');
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        $now = now();

        DB::table('payees')->insert(collect([
            'sales_partner' => 'Sales Partner',
            'sub_contractor' => 'Sub-Contractor',
            'supplier' => 'Supplier',
            'customer' => 'Customer',
            'others' => 'Others',
        ])->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'is_system' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ])->values()->all());
    }

    public function down(): void
    {
        Schema::dropIfExists('payees');
    }
};
