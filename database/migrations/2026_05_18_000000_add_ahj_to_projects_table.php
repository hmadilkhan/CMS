<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('ahj')->nullable()->after('hoa_phone_number');
        });

        DB::table('project_department_fields')->updateOrInsert([
            'department_id' => 1,
            'field_name' => 'ahj',
        ]);
    }

    public function down(): void
    {
        DB::table('project_department_fields')
            ->where('department_id', 1)
            ->where('field_name', 'ahj')
            ->delete();

        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('ahj');
        });
    }
};
