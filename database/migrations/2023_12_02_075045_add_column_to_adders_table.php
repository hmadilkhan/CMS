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
        Schema::table('adders', function (Blueprint $table) {
            $table->dropColumn("adder_type_id");
            // $table->renameColumn("adder_sub_type_id","adder_type_id");
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adders', function (Blueprint $table) {
            $table->integer("adder_type_id");
            // $table->renameColumn("adder_type_id","adder_sub_type_id");
        });
    }
};
