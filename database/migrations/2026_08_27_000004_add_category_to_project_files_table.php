<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Marks a project file as belonging to a named group rather than the
     * department's general file list - today only "utility_bill", the bill the
     * Utility Bill Follow Up collects. NULL means an ordinary file, which is
     * every file that already exists.
     *
     * A column rather than a header_text convention because header_text is
     * user-editable from the Files card - renaming a file would otherwise
     * silently drop it out of its group.
     */
    public function up(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->string('category')->nullable()->after('header_text');
            $table->index(['project_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('project_files', function (Blueprint $table) {
            $table->dropIndex(['project_id', 'category']);
            $table->dropColumn('category');
        });
    }
};
