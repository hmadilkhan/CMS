<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('finance_option_milestones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('finance_option_id')->constrained()->cascadeOnDelete();
            $table->string('key');
            $table->string('label');
            $table->string('trigger_type');
            $table->string('trigger_field')->nullable();
            $table->string('amount_type');
            $table->decimal('amount_value', 12, 2)->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['finance_option_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('finance_option_milestones');
    }
};
