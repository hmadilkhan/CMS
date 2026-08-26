<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Editable copy for the system emails the CRM sends on its own (ticket
     * assigned, note mention, milestone dates, ...). A row here overrides the
     * default defined in config/notification_templates.php; with no row the
     * default is used, so the feature degrades to the previous behaviour.
     */
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('subject');
            $table->text('body');
            $table->boolean('is_active')->default(true);
            $table->foreignId('updated_by')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
