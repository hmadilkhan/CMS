<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * "Dealer Fee Base" on a finance option: is the dealer fee charged on the
 * Contract Amount or on the Customer Portion (contract minus the TPO credits)?
 *
 * It used to be hard-coded to Wheelhouse Credit Union (finance_option_id 10 in
 * the customer and intake forms' script). Wheelhouse is switched to Customer
 * Portion here - matched by id or name, as the TPO split migration did - and
 * every other option, Prepaid PPA included, stays on Contract Amount, so no
 * figure changes.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->string('dealer_fee_base', 32)->default('contract_amount')->after('uses_tpo_split');
        });

        DB::table('finance_options')
            ->where('id', 10)
            ->orWhereRaw('LOWER(TRIM(name)) = ?', ['wheelhouse credit union'])
            ->update(['dealer_fee_base' => 'customer_portion']);
    }

    public function down(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->dropColumn('dealer_fee_base');
        });
    }
};
