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
        Schema::table('project_acceptances', function (Blueprint $table) {
            // Financial snapshot fields
            $table->decimal('inverter_base_price', 10, 2)->nullable();
            $table->decimal('dealer_fee_amount', 10, 2)->nullable();
            $table->decimal('module_qty_price', 10, 2)->nullable();
            $table->decimal('modules_amount', 10, 2)->nullable();
            $table->integer('panel_qty')->nullable();
            $table->decimal('contract_amount', 10, 2)->nullable();
            $table->decimal('redline_costs', 10, 2)->nullable();
            $table->decimal('adders_amount', 10, 2)->nullable();
            $table->decimal('commission_amount', 10, 2)->nullable();
            
            // Reference fields
            $table->string('inverter_name')->nullable();
            $table->text('adders_list')->nullable(); // JSON array of adder names
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('project_acceptances', function (Blueprint $table) {
            $table->dropColumn([
                'inverter_base_price',
                'dealer_fee_amount',
                'module_qty_price',
                'modules_amount',
                'panel_qty',
                'contract_amount',
                'redline_costs',
                'adders_amount',
                'commission_amount',
                'inverter_name',
                'adders_list'
            ]);
        });
    }
};
