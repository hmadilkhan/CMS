<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('project_invoice_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->cascadeOnDelete();
            $table->string('invoice_type');
            $table->date('invoice_date');
            $table->decimal('amount', 18, 2);
            $table->string('file_name');
            $table->string('original_file_name');
            $table->string('file_path');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();
        });

        if (!DB::table('permissions')->where('name', 'Invoice Details')->where('guard_name', 'web')->exists()) {
            DB::table('permissions')->insert([
                'name' => 'Invoice Details',
                'guard_name' => 'web',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('project_invoice_details');

        DB::table('permissions')
            ->where('name', 'Invoice Details')
            ->where('guard_name', 'web')
            ->delete();
    }
};
