<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_query_feedback', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ai_chat_message_id')->constrained('ai_chat_messages')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->enum('rating', ['up', 'down']);
            $table->text('comment')->nullable();
            $table->text('expected_result')->nullable();
            $table->timestamps();

            $table->unique(['ai_chat_message_id', 'user_id']);
        });

        Schema::create('ai_query_examples', function (Blueprint $table) {
            $table->id();
            $table->string('question', 500);
            $table->json('plan')->nullable();
            $table->text('sql')->nullable();
            $table->unsignedInteger('success_count')->default(0);
            $table->unsignedInteger('fail_count')->default(0);
            $table->integer('feedback_score')->default(0);
            $table->timestamps();

            $table->index('question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_query_examples');
        Schema::dropIfExists('ai_query_feedback');
    }
};
