<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->integer('pto_restriction')->default(0); // Yes/No  
            $table->integer('no_of_days')->default(0)->after('pto_restriction'); 
            $table->integer('holdback')->default(0); // Yes/No
            $table->decimal('dollar_watt_value')->default(0)->after('holdback');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->dropColumn('pto_restriction');
            $table->dropColumn('holdback');
            $table->dropColumn('no_of_days');
            $table->dropColumn('dollar_watt_value');
        });
    }
};
