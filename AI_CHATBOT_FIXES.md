# AI Chatbot Fixes & Improvements

## Executive Summary

Your AI chatbot has architectural and functional issues causing wrong answers. The root cause is **overly rigid intent-based system** that forces user questions into 13 predefined patterns with hardcoded SQL.

---

## Critical Security Fixes (Do First)

### 1. Enable Read-Only Database Connection

Your code already supports this, but you may not have it configured.

**Step 1: Create read-only MySQL user**
```sql
CREATE USER 'ai_readonly_user'@'%' IDENTIFIED BY 'your_secure_password';
GRANT SELECT ON your_crm_db.* TO 'ai_readonly_user'@'%';
FLUSH PRIVILEGES;
```

**Step 2: Add to `.env`**
```env
AI_READONLY_DB_CONNECTION=ai_readonly
DB_AI_PASSWORD=your_secure_password
```

**Step 3: Add to `config/database.php`**
```php
'connections' => [
    // ... existing connections
    
    'ai_readonly' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST'),
        'port' => env('DB_PORT'),
        'database' => env('DB_DATABASE'),
        'username' => 'ai_readonly_user',
        'password' => env('DB_AI_PASSWORD'),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'prefix_indexes' => true,
        'strict' => true,
        'engine' => null,
    ],
],
```

### 2. Strengthen SQL Validator

Add to `app/Services/AiSqlValidatorService.php`:

```php
private const BLOCKED_KEYWORDS = [
    'insert', 'update', 'delete', 'drop', 'alter', 
    'create', 'truncate', 'replace', 'grant', 'revoke',
    'union', 'union all', 'load_file', 'into outfile', 
    'sleep', 'benchmark', 'information_schema', 'procedure',
    'function', 'trigger', 'event'
];

public function validate(array $sqlPreview, array $plan, User $user): array
{
    $sql = (string) ($sqlPreview['sql'] ?? '');
    $normalizedSql = strtolower(trim($sql));

    // Must start with SELECT
    if ($normalizedSql === '' || ! str_starts_with($normalizedSql, 'select')) {
        return $this->reject('Only SELECT queries are allowed.');
    }
    
    // No subqueries in FROM (can bypass security)
    if (preg_match('/\(\s*SELECT\s+/i', $sql)) {
        return $this->reject('Subqueries are not allowed.');
    }

    foreach (self::BLOCKED_KEYWORDS as $keyword) {
        if (preg_match('/\b' . preg_quote($keyword, '/') . '\b/i', $sql)) {
            return $this->reject('This query contains a blocked SQL operation.');
        }
    }

    if (str_contains($sql, '--') || str_contains($sql, '/*') || str_contains($sql, '*/')) {
        return $this->reject('SQL comments are not allowed.');
    }

    if ($this->hasMultipleStatements($sql)) {
        return $this->reject('Multiple SQL statements are not allowed.');
    }

    $limit = $sqlPreview['limit'] ?? null;
    if (! is_int($limit) || $limit < 1 || $limit > 100 || ! preg_match('/\blimit\s+100\b/i', $sql)) {
        return $this->reject('A LIMIT of 100 or less is required.');
    }

    // Validate all referenced tables are allowed
    foreach ($sqlPreview['tables'] ?? [] as $table) {
        if (! $this->aiSchemaService->isTableAllowed($table)) {
            return $this->reject('The query references a table that is not allowed.');
        }
        if (! $this->aiPermissionService->canAccessTable($user, $table)) {
            return $this->reject('You do not have permission to access this information.');
        }
    }

    // existing validation continues...
}
```

### 3. Add Rate Limiting

**In `routes/web.php`:**
```php
Route::middleware(['auth', 'throttle:ai_chat'])->group(function () {
    Route::get('/ai-chat', [AiChatController::class, 'index'])->name('ai-chat.index');
    Route::post('/ai-chat/send', [AiChatController::class, 'send'])->name('ai-chat.send');
    Route::post('/ai-chat/{chat}/retry', [AiChatController::class, 'retry'])->name('ai-chat.retry');
    Route::patch('/ai-chat/{chat}', [AiChatController::class, 'rename'])->name('ai-chat.rename');
    Route::delete('/ai-chat/{chat}', [AiChatController::class, 'destroy'])->name('ai-chat.destroy');
    Route::get('/ai-chat/{chat}', [AiChatController::class, 'show'])->name('ai-chat.show');
});
```

**In `app/Providers/RouteServiceProvider.php`:**
```php
public function boot(): void
{
    RateLimiter::for('ai_chat', function (Request $request) {
        return Limit::perMinute(10)->by($request->user()->id);
    });
    
    // ... rest of method
}
```

---

## Fix Wrong Answers (Core Problem)

### Problem Analysis

Your current flow:
1. User asks question
2. AI classifies into 13 hardcoded intents
3. Your code generates SQL based on intent
4. SQL fails or returns wrong results → User gets bad answer

**The fix: Let AI generate SQL directly, then validate it.**

### Solution 1: Flexible Intent System

**In `app/Services/AiQueryPlannerService.php`:**

Remove strict intent enums:
```php
// REMOVE THIS:
// private const ALLOWED_INTENTS = [...];

// Change jsonSchema() method:
private function jsonSchema(): array
{
    return [
        'type' => 'json_schema',
        'name' => 'ai_query_plan',
        'strict' => true,
        'schema' => [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'answer_type', 'intent', 'tables', 'columns', 
                'group_by', 'filters', 'requires_finance_access',
                'sql', 'fallback_message',
            ],
            'properties' => [
                'answer_type' => [
                    'type' => 'string',
                    'enum' => ['text', 'table', 'card', 'count'],
                ],
                // INTENT IS NOW FREE TEXT, NOT ENUM
                'intent' => [
                    'type' => 'string',
                    'description' => 'Short label describing what the user wants (e.g., project_count, active_tickets, overdue_projects)',
                ],
                'tables' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'columns' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'group_by' => [
                    'type' => 'array',
                    'items' => ['type' => 'string'],
                ],
                'filters' => [
                    'type' => 'array',
                    'items' => [
                        'type' => 'object',
                        'additionalProperties' => false,
                        'required' => ['column', 'operator', 'value'],
                        'properties' => [
                            'column' => ['type' => 'string'],
                            'operator' => ['type' => 'string'],
                            'value' => ['type' => ['string', 'number', 'boolean', 'null']],
                        ],
                    ],
                ],
                'requires_finance_access' => ['type' => 'boolean'],
                'sql' => ['type' => 'null'], // AI does not generate SQL directly
                'fallback_message' => ['type' => ['string', 'null']],
            ],
        ],
    ];
}
```

### Solution 2: Let AI Generate SQL

**Create new method in `AiQueryPlannerService`:**

```php
public function generateSql(array $plan, User $user): array
{
    $schema = $this->schemaForSqlGeneration($plan['tables'] ?? []);
    
    $response = $this->openAiService->createJsonResponse(
        instructions: $this->sqlGenerationInstructions($user),
        input: [
            'question' => $plan['original_question'] ?? '',
            'plan' => $plan,
            'schema' => $schema,
            'user_role' => $this->userRole($user),
        ],
        jsonSchema: [
            'type' => 'json_schema',
            'name' => 'sql_generation',
            'strict' => true,
            'schema' => [
                'type' => 'object',
                'required' => ['sql', 'explanation'],
                'properties' => [
                    'sql' => ['type' => 'string'],
                    'explanation' => ['type' => 'string'],
                ],
            ],
        ],
        maxOutputTokens: 800
    );

    return [
        'sql' => $response['json']['sql'] ?? '',
        'explanation' => $response['json']['explanation'] ?? '',
        'raw_response' => $response,
    ];
}

private function sqlGenerationInstructions(User $user): string
{
    return <<<PROMPT
You are a SQL expert for a Laravel CRM. Generate a safe SELECT query based on the user's question and plan.

RULES:
1. Only use tables and columns from the provided schema
2. Always include LIMIT 100
3. Use LEFT JOINs for relationships
4. Add appropriate WHERE clauses for user role restrictions
5. Never use * (wildcard) - list specific columns
6. Never include comments in SQL
7. Use parameterized queries (placeholders for values)

User Role: {$this->userRole($user)}
PROMPT;
}

private function schemaForSqlGeneration(array $tables): array
{
    $schema = [];
    foreach ($tables as $table) {
        $config = $this->aiSchemaService->getTableConfig($table);
        $schema[$table] = [
            'columns' => $config['allowed_columns'] ?? [],
            'relationships' => $config['relationships'] ?? [],
        ];
    }
    return $schema;
}
```

### Solution 3: Self-Correction Loop

**Modify `AiChatService` to retry failed queries:**

```php
private function handleQueryPlan(AiChat $chat, User $user, string $message, AiQueryLog $log, float $startedAt): AiChat
{
    $maxAttempts = 2;
    $attempt = 0;
    $plan = null;
    $sqlPreview = null;
    $validation = null;
    $execution = null;
    $answer = null;
    $errors = [];

    while ($attempt < $maxAttempts) {
        $attempt++;
        
        // Generate plan (first attempt) or refine (subsequent)
        if ($attempt === 1) {
            $planned = $this->aiQueryPlannerService->plan($message, $user);
        } else {
            $planned = $this->aiQueryPlannerService->refinePlan(
                originalQuestion: $message,
                previousPlan: $plan,
                errors: $errors,
                user: $user
            );
        }
        
        $plan = $planned['plan'];
        $response = $planned['openai'];
        $usage = $response['usage'];

        if ($plan['intent'] === 'unknown') {
            break; // Can't help with this question
        }

        // Generate SQL
        $sqlPreview = $this->aiSqlBuilderService->build($plan, $user);
        $validation = $this->aiSqlValidatorService->validate($sqlPreview, $plan, $user);

        if (! ($validation['approved'] ?? false)) {
            $errors[] = 'Validation failed: ' . ($validation['reason'] ?? 'Unknown');
            continue; // Try again with refinement
        }

        $execution = $this->aiQueryExecutorService->execute($sqlPreview);

        if (! ($execution['success'] ?? false)) {
            $errors[] = 'Execution failed: ' . ($execution['error_message'] ?? 'Unknown');
            continue; // Try again with refinement
        }

        if (($execution['row_count'] ?? 0) === 0 && $attempt < $maxAttempts) {
            $errors[] = 'No results found';
            continue; // Try again with relaxed filters
        }

        // Success!
        break;
    }

    // Generate answer
    if ($plan['intent'] === 'unknown') {
        $assistantMessage = $plan['fallback_message'] ?: 'I could not understand that question. Try rephrasing or ask about projects, tickets, or customers.';
    } elseif (! ($validation['approved'] ?? false)) {
        $assistantMessage = 'I could not safely prepare this query. ' . ($validation['reason'] ?? 'Please try a different question.');
    } elseif (! ($execution['success'] ?? false)) {
        $assistantMessage = $execution['error_message'] ?? 'I could not run this query. Please try again.';
    } elseif (($execution['row_count'] ?? 0) === 0) {
        $assistantMessage = 'No data found for this request.';
        $answer = [
            'type' => 'text',
            'message' => $assistantMessage,
            'columns' => [],
            'rows' => [],
            'cards' => [],
        ];
    } else {
        $answer = $this->aiAnswerFormatterService->format($message, $plan, $execution);
        $assistantMessage = $answer['message'] ?? 'Here are the results.';
    }

    // ... rest of method (logging, storing message)
}
```

**Add to `AiQueryPlannerService`:**

```php
public function refinePlan(string $originalQuestion, array $previousPlan, array $errors, User $user): array
{
    $response = $this->openAiService->createJsonResponse(
        instructions: $this->refinementInstructions(),
        input: [
            'original_question' => $originalQuestion,
            'previous_plan' => $previousPlan,
            'errors' => $errors,
            'allowed_schema' => $this->schemaForPlanner(),
        ],
        jsonSchema: $this->jsonSchema()
    );

    $plan = $this->sanitizePlan($response['json'], $user);

    return [
        'plan' => $plan,
        'openai' => $response,
    ];
}

private function refinementInstructions(): string
{
    return <<<PROMPT
The previous query attempt failed. Based on the errors, refine the query plan.

Possible fixes:
- Relax filters if no results found
- Check table relationships if join failed
- Use different columns if column not found
- Simplify the query

Return a new plan that should work better.
PROMPT;
}
```

---

## Fix Hardcoded Values

### Remove Magic Numbers

**In `AiSqlBuilderService.php`, replace:**
```php
// BEFORE:
$excludesArchived ? [
    'column' => 'department_id',
    'operator' => '!=',
    'value' => 9,  // MAGIC NUMBER!
] : null,

// AFTER:
$excludesArchived ? [
    'column' => 'department_id',
    'operator' => '!=',
    'value' => config('ai.schema.archived_department_id', 9),
] : null,
```

**Add to `config/ai.php` (create if not exists):**
```php
<?php

return [
    'schema' => [
        'archived_department_id' => env('AI_ARCHIVED_DEPT_ID', 9),
        'max_query_limit' => 100,
        'max_retries' => 2,
    ],
    'security' => [
        'enable_readonly_connection' => true,
        'log_all_queries' => true,
        'max_daily_requests_per_user' => 100,
    ],
];
```

---

## Add Feedback System

### Database Migration

```php
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
            $table->foreignId('ai_chat_message_id')->constrained('ai_chat_messages')->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('rating', ['up', 'down']);
            $table->text('comment')->nullable();
            $table->text('expected_result')->nullable();
            $table->timestamps();
        });

        Schema::create('ai_query_examples', function (Blueprint $table) {
            $table->id();
            $table->text('question');
            $table->json('plan');
            $table->text('sql');
            $table->integer('success_count')->default(0);
            $table->integer('fail_count')->default(0);
            $table->integer('feedback_score')->default(0); // Sum of upvotes - downvotes
            $table->timestamps();
            
            $table->index('question');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_query_feedback');
        Schema::dropIfExists('ai_query_examples');
    }
};
```

### Controller Method

**Add to `AiChatController`:**

```php
public function feedback(Request $request, AiChatMessage $message)
{
    $validated = $request->validate([
        'rating' => 'required|in:up,down',
        'comment' => 'nullable|string|max:500',
        'expected_result' => 'nullable|string|max:1000',
    ]);

    // Store feedback
    AiQueryFeedback::create([
        'ai_chat_message_id' => $message->id,
        'user_id' => auth()->id(),
        'rating' => $validated['rating'],
        'comment' => $validated['comment'],
        'expected_result' => $validated['expected_result'],
    ]);

    // Update example score if exists
    $question = $message->chat->messages()
        ->where('role', 'user')
        ->where('created_at', '<', $message->created_at)
        ->latest()
        ->value('content');

    if ($question) {
        $example = AiQueryExample::where('question', $question)->first();
        if ($example) {
            $example->increment('feedback_score', $validated['rating'] === 'up' ? 1 : -1);
        }
    }

    return response()->json(['success' => true]);
}
```

### Frontend (Add to Blade Template)

```blade
@if ($message->role === 'assistant')
    <div class="mt-2 flex items-center gap-3">
        <span class="text-xs text-slate-400">Was this helpful?</span>
        <button onclick="submitFeedback({{ $message->id }}, 'up')" class="text-slate-400 hover:text-green-600">
            👍
        </button>
        <button onclick="submitFeedback({{ $message->id }}, 'down')" class="text-slate-400 hover:text-red-600">
            👎
        </button>
    </div>
    
    <!-- Feedback modal (hidden by default) -->
    <div id="feedback-modal-{{ $message->id }}" class="hidden mt-2 p-3 bg-slate-50 rounded-lg">
        <textarea id="feedback-comment-{{ $message->id }}" 
                  placeholder="What were you expecting?" 
                  class="w-full text-sm border-slate-200 rounded-md"></textarea>
        <button onclick="submitFeedbackWithComment({{ $message->id }})" 
                class="mt-2 px-3 py-1 bg-slate-800 text-white text-sm rounded-md">
            Submit
        </button>
    </div>
@endif

<script>
function submitFeedback(messageId, rating) {
    if (rating === 'down') {
        document.getElementById(`feedback-modal-${messageId}`).classList.remove('hidden');
        return;
    }
    
    fetch(`/ai-chat/messages/${messageId}/feedback`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf_token"]').content,
        },
        body: JSON.stringify({ rating }),
    });
}

function submitFeedbackWithComment(messageId) {
    const comment = document.getElementById(`feedback-comment-${messageId}`).value;
    
    fetch(`/ai-chat/messages/${messageId}/feedback`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf_token"]').content,
        },
        body: JSON.stringify({ 
            rating: 'down',
            comment 
        }),
    }).then(() => {
        document.getElementById(`feedback-modal-${messageId}`).classList.add('hidden');
    });
}
</script>
```

### Routes

```php
Route::post('/ai-chat/messages/{message}/feedback', [AiChatController::class, 'feedback'])
    ->name('ai-chat.feedback');
```

---

## Query Result Caching

**Add to `AiQueryExecutorService`:**

```php
<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiQueryExecutorService
{
    private const CACHE_TTL = 300; // 5 minutes

    public function execute(array $sqlPreview, ?int $userId = null): array
    {
        $cacheKey = $this->buildCacheKey($sqlPreview, $userId);
        
        // Check cache
        if ($cached = Cache::get($cacheKey)) {
            return array_merge($cached, ['cached' => true]);
        }

        try {
            $connectionName = config('database.ai_readonly_connection');
            $connection = $connectionName && config("database.connections.{$connectionName}")
                ? DB::connection($connectionName)
                : DB::connection();

            $rows = $connection->select($sqlPreview['sql'], $sqlPreview['bindings'] ?? []);

            $result = [
                'success' => true,
                'rows' => array_map(fn ($row) => (array) $row, $rows),
                'row_count' => count($rows),
                'connection' => $connectionName && config("database.connections.{$connectionName}") ? $connectionName : config('database.default'),
                'error_message' => null,
                'cached' => false,
            ];

            // Cache successful results
            if (count($rows) > 0) {
                Cache::put($cacheKey, $result, self::CACHE_TTL);
            }

            return $result;
        } catch (Throwable $exception) {
            Log::warning('AI query execution failed.', [
                'message' => $exception->getMessage(),
                'sql' => $sqlPreview['sql'] ?? null,
            ]);

            return [
                'success' => false,
                'rows' => [],
                'row_count' => 0,
                'connection' => config('database.ai_readonly_connection') ?: config('database.default'),
                'error_message' => 'I could not safely run this CRM query. Please try again or contact an administrator.',
                'cached' => false,
            ];
        }
    }

    private function buildCacheKey(array $sqlPreview, ?int $userId): string
    {
        $sql = $sqlPreview['sql'] ?? '';
        $bindings = $sqlPreview['bindings'] ?? [];
        
        return 'ai_query:' . md5($sql . serialize($bindings) . ($userId ?? 'guest'));
    }

    public function clearCache(): void
    {
        // Optional: Clear all AI query caches
        // Can be called when data changes significantly
    }
}
```

---

## Daily Token Limits Per User

**Add middleware:**

```php
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class AiRateLimiter
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        if (! $user) {
            return $next($request);
        }

        $cacheKey = "ai_daily_usage:{$user->id}:" . now()->format('Y-m-d');
        $currentUsage = Cache::get($cacheKey, 0);
        
        $maxDaily = config('ai.security.max_daily_requests_per_user', 100);
        
        if ($currentUsage >= $maxDaily) {
            return response()->json([
                'message' => 'Daily AI request limit reached. Please try again tomorrow.',
            ], 429);
        }

        // Increment usage (24 hour TTL)
        Cache::put($cacheKey, $currentUsage + 1, now()->addDay());

        return $next($request);
    }
}
```

**Register in `app/Http/Kernel`:**
```php
protected $middlewareAliases = [
    // ... existing aliases
    'ai.daily.limit' => \App\Http\Middleware\AiRateLimiter::class,
];
```

**Apply to routes:**
```php
Route::middleware(['auth', 'throttle:ai_chat', 'ai.daily.limit'])->group(function () {
    Route::post('/ai-chat/send', [AiChatController::class, 'send']);
    Route::post('/ai-chat/{chat}/retry', [AiChatController::class, 'retry']);
});
```

---

## Better Schema Descriptions

**Update `config/ai_schema.php`:**

```php
<?php

return [
    'tables' => [
        'projects' => [
            'model' => \App\Models\Project::class,
            'table' => 'projects',
            'description' => 'Solar installation projects. Each row represents one customer project from sale to completion.',
            'allowed_columns' => [
                'id' => ['type' => 'bigint', 'description' => 'Unique project identifier'],
                'customer_id' => ['type' => 'bigint', 'description' => 'Links to customers table'],
                'project_name' => ['type' => 'string', 'description' => 'Display name for the project'],
                'department_id' => ['type' => 'bigint', 'description' => 'Current workflow stage: 1=Sales, 2=Design, 3=Permitting, 4=Installation'],
                // ... other columns
            ],
            'searchable_columns' => ['project_name', 'code', 'description'],
            'relationships' => [
                'customer' => [
                    'table' => 'customers',
                    'local_key' => 'customer_id',
                    'foreign_key' => 'id',
                    'description' => 'Customer who owns this project',
                ],
            ],
            'common_filters' => [
                'active' => 'deleted_at IS NULL',
                'by_department' => 'department_id = ?',
            ],
            'access_rule' => 'project_access',
        ],
        // ... other tables
    ],
];
```

**Update `AiSchemaService` to read new format:**
```php
public function getTableDescription(string $table): string
{
    $config = $this->getTableConfig($table);
    return $config['description'] ?? '';
}

public function getColumnDescription(string $table, string $column): string
{
    $config = $this->getTableConfig($table);
    $columns = $config['allowed_columns'] ?? [];
    
    if (is_array($columns[$column] ?? null)) {
        return $columns[$column]['description'] ?? '';
    }
    
    return '';
}
```

---

## Query Timeout Protection

**Add to `AiQueryExecutorService`:**

```php
public function execute(array $sqlPreview, ?int $userId = null): array
{
    try {
        $connectionName = config('database.ai_readonly_connection');
        $connection = $connectionName && config("database.connections.{$connectionName}")
            ? DB::connection($connectionName)
            : DB::connection();

        // Set statement timeout (MySQL 5.7+)
        $connection->statement('SET SESSION MAX_EXECUTION_TIME=5000'); // 5 seconds

        $rows = $connection->select($sqlPreview['sql'], $sqlPreview['bindings'] ?? []);

        // Reset timeout
        $connection->statement('SET SESSION MAX_EXECUTION_TIME=0');

        // ... rest of method
    } catch (Throwable $exception) {
        // Check if timeout error
        if (str_contains($exception->getMessage(), 'timeout') || 
            str_contains($exception->getMessage(), 'exceeded')) {
            return [
                'success' => false,
                'error_message' => 'Query took too long. Please ask a more specific question.',
            ];
        }
        
        // ... existing error handling
    }
}
```

---

## Implementation Checklist

### Week 1: Security (Critical)
- [ ] Create read-only MySQL user
- [ ] Configure `AI_READONLY_DB_CONNECTION` in `.env`
- [ ] Add new blocked keywords to SQL validator
- [ ] Add rate limiting to routes
- [ ] Add query timeout protection

### Week 2: Fix Wrong Answers
- [ ] Remove intent enum restrictions
- [ ] Add self-correction loop with 2 retry attempts
- [ ] Remove hardcoded department ID (use config)
- [ ] Add query result caching (5 min TTL)

### Week 3: Feedback & Improvement
- [ ] Create migration for feedback/examples tables
- [ ] Add thumbs up/down buttons to UI
- [ ] Create feedback controller method
- [ ] Store successful queries as examples

### Week 4: Monitoring
- [ ] Add daily token limit per user
- [ ] Log IP address and user agent
- [ ] Set up alerts for failed validations
- [ ] Create admin dashboard for AI usage stats

---

## Quick Test Script

Create `test-ai-chat.php` to verify fixes:

```php
<?php

require __DIR__ . '/vendor/autoload.php';

$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Services\AiChatService;
use App\Models\User;

$user = User::first(); // Get a test user
$service = app(AiChatService::class);

$testQuestions = [
    'How many projects do I have?',
    'Show me pending tickets',
    'Projects in Sales department',
    'What is my commission?', // Should require finance access
    'Delete all projects', // Should be blocked
];

foreach ($testQuestions as $question) {
    echo "\nQuestion: {$question}\n";
    try {
        $chat = $service->send($user, $question);
        $lastMessage = $chat->messages->last();
        echo "Response: {$lastMessage->content}\n";
        echo "Status: " . ($lastMessage->metadata['status'] ?? 'unknown') . "\n";
    } catch (Exception $e) {
        echo "Error: {$e->getMessage()}\n";
    }
}
```

Run: `php test-ai-chat.php`

---

## Summary

| Problem | Solution |
|---------|----------|
| Wrong answers | Let AI generate SQL directly + self-correction loop |
| Hardcoded SQL | Remove intent-based SQL building, use AI generation |
| Security risk | Read-only DB user + strict SQL validation |
| No feedback | Add thumbs up/down with comment system |
| Expensive API calls | Add caching + daily limits |
| Slow queries | Add query timeouts + result caching |
| No improvement over time | Store successful examples for few-shot learning |

**Start with security fixes first, then work on answer quality improvements.**
