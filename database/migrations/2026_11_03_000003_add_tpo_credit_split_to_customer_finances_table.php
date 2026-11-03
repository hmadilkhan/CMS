<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Third Party Credit is now entered as a Solar TPO Credit and a Battery TPO
 * Credit. Existing rows are left exactly as they are: tpo_split stays 0 for
 * them, which is how the forms know to keep showing their single figure.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_finances', function (Blueprint $table) {
            $table->decimal('solar_tpo_credit', 10, 2)->default(0)->after('third_party_credit');
            $table->decimal('battery_tpo_credit', 10, 2)->default(0)->after('solar_tpo_credit');
            $table->boolean('tpo_split')->default(false)->after('battery_tpo_credit');
        });
    }

    public function down(): void
    {
        Schema::table('customer_finances', function (Blueprint $table) {
            $table->dropColumn(['solar_tpo_credit', 'battery_tpo_credit', 'tpo_split']);
        });
    }
};
