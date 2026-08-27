<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Installation > Install Pending Document - the only closed lane today. */
    private const INSTALL_PENDING_DOCUMENT_ID = 31;

    /**
     * A closed lane is one nobody may move a project into by hand, and one no
     * project may be moved out of by hand either - it is reached and left only
     * by the system (the Document Follow Up releases Install Pending Document
     * once the meter spot result arrives). Every lane is open by default.
     */
    public function up(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->boolean('show_in_move_list')->default(true)->after('order');
        });

        DB::table('sub_departments')
            ->where('id', self::INSTALL_PENDING_DOCUMENT_ID)
            ->update(['show_in_move_list' => false]);
    }

    public function down(): void
    {
        Schema::table('sub_departments', function (Blueprint $table) {
            $table->dropColumn('show_in_move_list');
        });
    }
};
