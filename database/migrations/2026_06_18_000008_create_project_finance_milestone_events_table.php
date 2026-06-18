<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('project_finance_milestone_events');

        Schema::create('project_finance_milestone_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('finance_option_milestone_id');
            $table->string('key');
            $table->decimal('base_amount', 12, 2)->default(0);
            $table->decimal('amount', 12, 2)->default(0);
            $table->timestamp('triggered_at')->nullable();
            $table->unsignedBigInteger('account_transaction_id')->nullable();
            $table->json('email_recipients')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'key']);
            $table->foreign('project_id', 'pfme_project_fk')->references('id')->on('projects')->cascadeOnDelete();
            $table->foreign('finance_option_milestone_id', 'pfme_milestone_fk')->references('id')->on('finance_option_milestones')->cascadeOnDelete();
            $table->foreign('account_transaction_id', 'pfme_transaction_fk')->references('id')->on('account_transactions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('project_finance_milestone_events');
    }
};
