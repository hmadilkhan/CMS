<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_finances', function (Blueprint $table) {
            $table->decimal('third_party_credit', 10, 2)->default(0)->after('dealer_fee_amount');
            $table->decimal('customer_portion', 10, 2)->default(0)->after('third_party_credit');
        });
    }

    public function down(): void
    {
        Schema::table('customer_finances', function (Blueprint $table) {
            $table->dropColumn(['third_party_credit', 'customer_portion']);
        });
    }
};
