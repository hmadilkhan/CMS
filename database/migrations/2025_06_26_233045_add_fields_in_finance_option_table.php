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
            $table->integer('loan_id')->default(0)->after('name'); // 0 for No Loan, 1 for Loan
            $table->integer('production_requirements')->default(0)->after('loan_id'); // 0 for No production_requirements, 1 for production_requirements    
            $table->decimal('positive_variance')->nullable()->after('production_requirements');
            $table->decimal('negative_variance')->nullable()->after('positive_variance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('finance_options', function (Blueprint $table) {
            $table->dropColumn('loan_id');
            $table->dropColumn('production_requirements');
            $table->dropColumn('positive_variance');
            $table->dropColumn('negative_variance');
        });
    }
};
