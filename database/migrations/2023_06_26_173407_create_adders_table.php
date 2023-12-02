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
        Schema::create('adders', function (Blueprint $table) {
            $table->id();
            $table->integer("adder_type_id");
            // $table->integer("adder_sub_type_id");
            $table->integer("adder_unit_id");
            $table->double("price",18,2)->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adders');
    }
};
