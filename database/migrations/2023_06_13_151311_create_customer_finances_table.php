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
        Schema::create('customer_finances', function (Blueprint $table) {
            $table->id();
            $table->integer("customer_id");
            $table->integer("finance_option_id");
            $table->float("contract_amount");
            $table->float("redline_costs");
            $table->string("adders");
            $table->float("commission");
            $table->float("dealer_fee");
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customer_finances');
    }
};
