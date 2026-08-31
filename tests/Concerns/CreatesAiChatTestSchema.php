<?php

namespace Tests\Concerns;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

trait CreatesAiChatTestSchema
{
    /**
     * Environment values overwritten by useSqliteTestDatabase(), so they can be
     * put back afterwards.
     *
     * @var array<string, string|false>
     */
    private array $originalDatabaseEnv = [];

    /**
     * Point the process at an in-memory SQLite database.
     *
     * These tests build their own schema instead of running the CRM's MySQL
     * migrations, so they need SQLite before the application boots. The values
     * are process-wide, which is why restoreDatabaseEnv() must undo them —
     * otherwise every test that runs afterwards in the same process silently
     * moves off the MySQL test database too.
     */
    protected function useSqliteTestDatabase(): void
    {
        foreach (['DB_CONNECTION' => 'sqlite', 'DB_DATABASE' => ':memory:'] as $key => $value) {
            $this->originalDatabaseEnv[$key] = getenv($key);

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }

    /**
     * Put the database environment back the way the test runner set it.
     */
    protected function restoreDatabaseEnv(): void
    {
        foreach ($this->originalDatabaseEnv as $key => $value) {
            if ($value === false) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);

                continue;
            }

            putenv("{$key}={$value}");
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->originalDatabaseEnv = [];
    }

    protected function createAiChatTestSchema(): void
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('username')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->integer('user_type_id')->nullable();
            $table->integer('sales_partner_id')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('guard_name');
            $table->timestamps();
            $table->unique(['name', 'guard_name']);
        });

        Schema::create('model_has_roles', function (Blueprint $table) {
            $table->unsignedBigInteger('role_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['role_id', 'model_id', 'model_type']);
        });

        Schema::create('model_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
            $table->primary(['permission_id', 'model_id', 'model_type']);
        });

        Schema::create('role_has_permissions', function (Blueprint $table) {
            $table->unsignedBigInteger('permission_id');
            $table->unsignedBigInteger('role_id');
            $table->primary(['permission_id', 'role_id']);
        });

        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('code')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('employee_departments', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('department_id');
            $table->timestamps();
        });

        // The CRM tables the assistant reaches for on its deterministic routes.
        // They are here rather than in individual tests because several fast
        // paths (project detail, row scoping, entity resolution) query them
        // before any mock gets a chance to intercept.
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();
            $table->unsignedBigInteger('sales_partner_id')->nullable();
            $table->unsignedBigInteger('sub_contractor_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('departments', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('projects', function (Blueprint $table) {
            $table->id();
            $table->string('project_name')->nullable();
            $table->string('code')->nullable();
            $table->unsignedBigInteger('customer_id')->nullable();
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('sub_department_id')->nullable();
            $table->unsignedBigInteger('sub_contractor_user_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('project_id');
            $table->unsignedBigInteger('department_id')->nullable();
            $table->unsignedBigInteger('employee_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('status')->nullable();
            $table->text('notes')->nullable();
            $table->text('assign_to_notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('service_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->string('subject')->nullable();
            $table->string('priority')->nullable();
            $table->string('status')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('ai_chats', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->string('title')->default('New chat');
            $table->string('openai_response_id')->nullable();
            $table->timestamp('last_message_at')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_chat_id');
            $table->string('role');
            $table->longText('content');
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_query_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_chat_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('provider')->default('openai');
            $table->string('model')->nullable();
            $table->string('status')->default('pending');
            $table->string('response_id')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->unsignedInteger('total_tokens')->nullable();
            $table->unsignedTinyInteger('openai_calls')->nullable();
            $table->unsignedInteger('openai_ms')->nullable();
            $table->unsignedInteger('db_ms')->nullable();
            $table->string('engine', 32)->nullable();
            $table->unsignedTinyInteger('fallbacks')->default(0);
            $table->json('stage_timings')->nullable();
            $table->char('question_hash', 32)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('request_payload')->nullable();
            $table->json('response_payload')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_query_feedback', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('ai_chat_message_id');
            $table->unsignedBigInteger('user_id');
            $table->string('rating');
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
        });
    }
}
