<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('adder_types', function (Blueprint $table) {
            $table->string('tag')->nullable()->after('name');
        });

        Schema::table('inverter_types', function (Blueprint $table) {
            $table->json('tags')->nullable()->after('name');
        });
    }

    public function down(): void
    {
        Schema::table('adder_types', function (Blueprint $table) {
            $table->dropColumn('tag');
        });

        Schema::table('inverter_types', function (Blueprint $table) {
            $table->dropColumn('tags');
        });
    }
};
