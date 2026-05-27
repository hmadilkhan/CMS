# AI Chatbot Fixes for Shared Hosting (Hostinger)

## Problem

Shared hosting doesn't allow creating database users with restricted privileges. You cannot create a read-only MySQL user, which is the standard security practice for AI chatbots.

## Solution Overview

Implement **application-level security** with multiple defense layers:

1. **SQL Parser** - Validate SQL before execution
2. **Query Whitelist** - Only allow pre-approved queries
3. **Query Builder** - Use Laravel's parameterized queries (never raw SQL)
4. **Write Blocking** - Block write operations at application level
5. **Monitoring** - Daily integrity checks and alerts

---

## Layer 1: SQL Parser Service

Create `app/Services/AiSqlParserService.php`:

```php
<?php

namespace App\Services;

class AiSqlParserService
{
    private const ALLOWED_KEYWORDS = [
        'select', 'from', 'where', 'and', 'or', 'not', 
        'left', 'right', 'inner', 'outer', 'join', 'on',
        'as', 'distinct', 'limit', 'offset', 'order', 'by',
        'asc', 'desc', 'group', 'having', 'count', 'sum',
        'avg', 'min', 'max', 'is', 'null', 'between', 'like',
        'in', 'exists', 'case', 'when', 'then', 'else', 'end',
        'coalesce', 'ifnull', 'date', 'year', 'month', 'day'
    ];

    private const BLOCKED_KEYWORDS = [
        'insert', 'update', 'delete', 'drop', 'alter', 'create',
        'truncate', 'replace', 'grant', 'revoke', 'exec', 'execute',
        'union', 'union all', 'load_file', 'into outfile', 
        'sleep', 'benchmark', 'information_schema', 'procedure',
        'function', 'trigger', 'event', 'lock', 'unlock'
    ];

    public function validate(string $sql): array
    {
        $normalized = strtolower(trim($sql));
        
        // Must start with SELECT
        if (!str_starts_with($normalized, 'select')) {
            return ['valid' => false, 'error' => 'Only SELECT queries allowed'];
        }

        // Tokenize and check each word
        $tokens = preg_split('/\s+/', preg_replace('/[\'"][^\'"]*[\'"]/', '', $normalized));
        
        foreach ($tokens as $token) {
            $cleanToken = preg_replace('/[^a-z_]/', '', $token);
            
            if (in_array($cleanToken, self::BLOCKED_KEYWORDS)) {
                return ['valid' => false, 'error' => "Blocked keyword: {$cleanToken}"];
            }
        }

        // Check for multiple statements
        if (substr_count($sql, ';') > 1 || (str_contains($sql, ';') && !str_ends_with(trim($sql), ';'))) {
            return ['valid' => false, 'error' => 'Multiple statements not allowed'];
        }

        // Check for comments
        if (str_contains($sql, '--') || str_contains($sql, '/*') || str_contains($sql, '*/')) {
            return ['valid' => false, 'error' => 'Comments not allowed'];
        }

        // Extract and validate tables
        $tables = $this->extractTables($sql);
        foreach ($tables as $table) {
            if (!$this->isAllowedTable($table)) {
                return ['valid' => false, 'error' => "Table not allowed: {$table}"];
            }
        }

        return ['valid' => true, 'tables' => $tables];
    }

    private function extractTables(string $sql): array
    {
        $tables = [];
        
        // Match FROM and JOIN clauses
        preg_match_all('/\b(from|join)\s+`?(\w+)`?/i', $sql, $matches);
        
        if (!empty($matches[2])) {
            $tables = array_unique($matches[2]);
        }
        
        return $tables;
    }

    private function isAllowedTable(string $table): bool
    {
        $allowed = array_keys(config('ai_schema.tables', []));
        return in_array($table, $allowed);
    }
}
```

---

## Layer 2: Query Whitelist Config

Create `config/ai_queries.php`:

```php
<?php

return [
    // User's own projects
    'my_projects' => [
        'description' => 'Get current user assigned projects',
        'sql_template' => "SELECT p.id, p.project_name, p.code, d.name as department, p.start_date 
                          FROM projects p 
                          LEFT JOIN departments d ON p.department_id = d.id 
                          WHERE p.id IN (
                              SELECT project_id FROM tasks WHERE employee_id = (
                                  SELECT id FROM employees WHERE user_id = ?
                              )
                          ) 
                          AND p.deleted_at IS NULL 
                          ORDER BY p.created_at DESC 
                          LIMIT 100",
        'parameters' => ['user_id'],
        'allowed_roles' => ['Employee', 'Manager', 'Admin', 'Super Admin'],
    ],
    
    // Project count by status
    'project_count_by_status' => [
        'description' => 'Count projects grouped by status',
        'sql_template' => "SELECT t.status, COUNT(DISTINCT p.id) as count 
                          FROM projects p 
                          JOIN tasks t ON p.id = t.project_id 
                          WHERE p.deleted_at IS NULL 
                          GROUP BY t.status 
                          LIMIT 100",
        'parameters' => [],
        'allowed_roles' => ['Admin', 'Manager', 'Super Admin', 'Finance'],
    ],
    
    // Pending tickets
    'pending_tickets' => [
        'description' => 'Get pending service tickets',
        'sql_template' => "SELECT st.subject, st.priority, st.status, u.name as assigned_to, st.created_at 
                          FROM service_tickets st 
                          LEFT JOIN users u ON st.assigned_to = u.id 
                          WHERE st.status = 'Pending' 
                          AND st.created_at >= DATE_SUB(NOW(), INTERVAL 90 DAY)
                          ORDER BY st.created_at DESC 
                          LIMIT 100",
        'parameters' => [],
        'allowed_roles' => ['Admin', 'Manager', 'Employee', 'Super Admin'],
    ],
    
    // Projects by department
    'projects_by_department' => [
        'description' => 'Get projects in specific department',
        'sql_template' => "SELECT p.id, p.project_name, p.code, c.first_name, c.last_name, p.created_at 
                          FROM projects p 
                          LEFT JOIN customers c ON p.customer_id = c.id 
                          LEFT JOIN departments d ON p.department_id = d.id 
                          WHERE d.name LIKE ? 
                          AND p.deleted_at IS NULL 
                          ORDER BY p.created_at DESC 
                          LIMIT 100",
        'parameters' => ['department_name'],
        'allowed_roles' => ['Admin', 'Manager', 'Super Admin'],
    ],
    
    // Active projects count
    'active_projects_count' => [
        'description' => 'Count of active projects',
        'sql_template' => "SELECT COUNT(*) as count 
                          FROM projects 
                          WHERE deleted_at IS NULL 
                          AND department_id NOT IN (
                              SELECT id FROM departments WHERE name LIKE '%archive%'
                          )",
        'parameters' => [],
        'allowed_roles' => ['Admin', 'Manager', 'Super Admin', 'Finance', 'Employee'],
    ],
    
    // Customer list
    'customer_list' => [
        'description' => 'List of customers with basic info',
        'sql_template' => "SELECT id, first_name, last_name, email, phone, city, state, created_at 
                          FROM customers 
                          WHERE deleted_at IS NULL 
                          ORDER BY created_at DESC 
                          LIMIT 100",
        'parameters' => [],
        'allowed_roles' => ['Admin', 'Manager', 'Super Admin', 'Sales Manager'],
    ],
    
    // Tasks assigned to user
    'my_tasks' => [
        'description' => 'Get tasks assigned to current user',
        'sql_template' => "SELECT t.id, t.notes, t.status, p.project_name, p.code, d.name as department 
                          FROM tasks t 
                          LEFT JOIN projects p ON t.project_id = p.id 
                          LEFT JOIN departments d ON t.department_id = d.id 
                          WHERE t.employee_id = (
                              SELECT id FROM employees WHERE user_id = ?
                          )
                          AND t.deleted_at IS NULL 
                          ORDER BY t.created_at DESC 
                          LIMIT 100",
        'parameters' => ['user_id'],
        'allowed_roles' => ['Employee', 'Manager', 'Super Admin'],
    ],
    
    // Finance summary (restricted)
    'finance_summary' => [
        'description' => 'Get project finance summary',
        'sql_template' => "SELECT p.project_name, p.code, cf.contract_amount, cf.finance_option 
                          FROM customer_finances cf 
                          LEFT JOIN customers c ON cf.customer_id = c.id 
                          LEFT JOIN projects p ON p.customer_id = c.id 
                          WHERE cf.contract_amount IS NOT NULL 
                          ORDER BY cf.created_at DESC 
                          LIMIT 100",
        'parameters' => [],
        'allowed_roles' => ['Finance', 'Admin', 'Super Admin'],
    ],
    
    // Add more queries as needed...
];
```

---

## Layer 3: Modified Query Planner

Update `app/Services/AiQueryPlannerService.php`:

```php
<?php

namespace App\Services;

use App\Models\User;

class AiQueryPlannerService
{
    private const ALLOWED_ANSWER_TYPES = ['text', 'table', 'card', 'count'];

    public function __construct(
        private readonly OpenAiService $openAiService,
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService
    ) {
    }

    public function plan(string $question, User $user): array
    {
        // First, try to match to whitelist (SAFEST)
        $matchedQuery = $this->matchToWhitelist($question, $user);
        
        if ($matchedQuery) {
            return [
                'plan' => [
                    'type' => 'whitelisted',
                    'query_key' => $matchedQuery['key'],
                    'parameters' => $matchedQuery['parameters'],
                    'requires_finance_access' => false,
                    'answer_type' => $this->inferAnswerType($matchedQuery['key']),
                ],
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }
        
        // If strict mode enabled, don't allow AI-generated queries
        if (config('ai.strict_mode', true)) {
            return [
                'plan' => [
                    'type' => 'unknown',
                    'fallback_message' => 'I can only answer specific pre-approved questions. Try asking about: your projects, pending tickets, project counts, or customer lists.',
                ],
                'openai' => $this->syntheticOpenAiResponse(),
            ];
        }
        
        // Fall back to AI planning with heavy restrictions
        return $this->planWithRestrictions($question, $user);
    }

    private function matchToWhitelist(string $question, User $user): ?array
    {
        $question = strtolower($question);
        $whitelist = config('ai_queries', []);
        $bestMatch = null;
        $bestScore = 0;
        
        foreach ($whitelist as $key => $config) {
            // Check role permission first
            if (!$user->hasAnyRole($config['allowed_roles'])) {
                continue;
            }
            
            // Calculate match score
            $score = $this->calculateMatchScore($question, $config['description'], $key);
            
            if ($score > $bestScore && $score >= 0.6) { // 60% match threshold
                $bestScore = $score;
                $bestMatch = [
                    'key' => $key,
                    'config' => $config,
                    'parameters' => $this->extractParameters($question, $config['parameters']),
                ];
            }
        }
        
        return $bestMatch;
    }

    private function calculateMatchScore(string $question, string $description, string $key): float
    {
        $descriptionWords = explode(' ', strtolower($description));
        $keyWords = explode('_', strtolower($key));
        $questionWords = explode(' ', preg_replace('/[^a-z0-9\s]/', '', $question));
        
        $matches = 0;
        $totalWords = count(array_unique(array_merge($descriptionWords, $keyWords)));
        
        foreach (array_merge($descriptionWords, $keyWords) as $word) {
            if (strlen($word) < 3) continue; // Skip short words
            
            foreach ($questionWords as $qWord) {
                if (str_contains($qWord, $word) || str_contains($word, $qWord)) {
                    $matches++;
                    break;
                }
            }
        }
        
        return $matches / $totalWords;
    }

    private function extractParameters(string $question, array $paramConfig): array
    {
        $params = [];
        
        foreach ($paramConfig as $param) {
            if ($param === 'user_id') {
                $params[$param] = auth()->id();
            } else {
                // Extract from question using simple patterns
                $params[$param] = $this->extractParameterFromQuestion($question, $param);
            }
        }
        
        return $params;
    }

    private function extractParameterFromQuestion(string $question, string $param): ?string
    {
        // Extract department names
        if ($param === 'department_name') {
            if (preg_match('/(sales|design|permitting|installation|inspection|pto)\s*(department)?/i', $question, $matches)) {
                return '%' . $matches[1] . '%';
            }
        }
        
        return null;
    }

    private function inferAnswerType(string $queryKey): string
    {
        if (str_contains($queryKey, 'count')) {
            return 'count';
        }
        if (str_contains($queryKey, 'list') || str_contains($queryKey, 'projects') || str_contains($queryKey, 'tickets')) {
            return 'table';
        }
        return 'table';
    }

    private function syntheticOpenAiResponse(): array
    {
        return [
            'id' => null,
            'model' => config('services.openai.model', 'gpt-4.1-mini'),
            'usage' => [],
            'payload' => [],
            'raw' => [],
        ];
    }

    // Keep existing methods for non-strict mode...
}
```

---

## Layer 4: Write Operation Blocker

Add to `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Block write operations from AI chat context
        DB::listen(function ($query) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 15);
            $isAiChat = false;
            
            foreach ($backtrace as $frame) {
                if (isset($frame['class'])) {
                    if (str_contains($frame['class'], 'Ai') || 
                        str_contains($frame['class'], 'AiChat')) {
                        $isAiChat = true;
                        break;
                    }
                }
                if (isset($frame['file']) && str_contains($frame['file'], 'AiChat')) {
                    $isAiChat = true;
                    break;
                }
            }
            
            if ($isAiChat) {
                $sql = strtolower(trim($query->sql));
                
                $writeOps = [
                    'insert', 'update', 'delete', 'drop', 'alter', 
                    'create', 'truncate', 'replace', 'grant', 'revoke'
                ];
                
                foreach ($writeOps as $op) {
                    if (str_starts_with($sql, $op)) {
                        \Log::critical('AI write operation blocked', [
                            'sql' => $query->sql,
                            'user' => auth()->id(),
                            'ip' => request()->ip(),
                        ]);
                        
                        throw new \Exception("Write operation '{$op}' is not allowed in AI chat context");
                    }
                }
            }
        });
    }
}
```

---

## Layer 5: Modified SQL Validator

Update `app/Services/AiSqlValidatorService.php`:

```php
<?php

namespace App\Services;

use App\Models\User;

class AiSqlValidatorService
{
    private const BLOCKED_KEYWORDS = [
        'insert', 'update', 'delete', 'drop', 'alter', 'create',
        'truncate', 'replace', 'grant', 'revoke', 'exec', 'execute',
        'union', 'union all', 'load_file', 'into outfile', 
        'sleep', 'benchmark', 'information_schema', 'procedure',
        'function', 'trigger', 'event', 'lock', 'unlock'
    ];

    public function __construct(
        private readonly AiSchemaService $aiSchemaService,
        private readonly AiPermissionService $aiPermissionService,
        private readonly AiSqlParserService $sqlParser
    ) {
    }

    public function validate(array $sqlPreview, array $plan, User $user): array
    {
        // Use parser first
        $parseResult = $this->sqlParser->validate($sqlPreview['sql'] ?? '');
        
        if (!$parseResult['valid']) {
            return $this->reject($parseResult['error']);
        }

        $sql = (string) ($sqlPreview['sql'] ?? '');
        $normalizedSql = strtolower(trim($sql));

        if ($normalizedSql === '' || ! str_starts_with($normalizedSql, 'select')) {
            return $this->reject('Only SELECT queries are allowed.');
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

        foreach ($sqlPreview['tables'] ?? [] as $table) {
            if (! $this->aiSchemaService->isTableAllowed($table)) {
                return $this->reject('The query references a table that is not allowed.');
            }

            if (! $this->aiPermissionService->canAccessTable($user, $table)) {
                return $this->reject('You do not have permission to access this information.');
            }
        }

        if (($plan['requires_finance_access'] ?? false) && ! $this->aiPermissionService->canAccessFinance($user)) {
            return $this->reject('You do not have permission to access this information.');
        }

        return [
            'approved' => true,
            'status' => 'approved',
            'reason' => null,
        ];
    }

    private function reject(string $reason): array
    {
        return [
            'approved' => false,
            'status' => 'rejected',
            'reason' => $reason,
        ];
    }

    private function hasMultipleStatements(string $sql): bool
    {
        return str_contains(rtrim($sql), ';');
    }
}
```

---

## Layer 6: Data Integrity Monitoring

Create `app/Console/Commands/CheckAiDataIntegrity.php`:

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class CheckAiDataIntegrity extends Command
{
    protected $signature = 'ai:check-integrity';
    protected $description = 'Check if AI chat caused any data changes';

    public function handle()
    {
        $tables = ['projects', 'customers', 'users', 'tasks', 'service_tickets'];
        $counts = [];
        $changes = [];
        
        foreach ($tables as $table) {
            $counts[$table] = DB::table($table)->count();
        }
        
        $yesterdayCounts = cache()->get('ai_integrity_counts', []);
        
        foreach ($tables as $table) {
            if (isset($yesterdayCounts[$table])) {
                $diff = $counts[$table] - $yesterdayCounts[$table];
                
                if ($diff !== 0) {
                    $changes[] = "{$table}: {$diff} rows";
                }
            }
        }
        
        if (!empty($changes)) {
            $message = "ALERT: Data changes detected:\n" . implode("\n", $changes);
            
            \Log::critical($message);
            
            // Send email if configured
            if (config('mail.admin_address')) {
                Mail::raw($message, function ($msg) {
                    $msg->to(config('mail.admin_address'))
                        ->subject('AI Chat Data Integrity Alert');
                });
            }
        }
        
        cache()->put('ai_integrity_counts', $counts, now()->addDays(2));
        
        $this->info('Integrity check complete. Changes: ' . count($changes));
    }
}
```

**Register in `app/Console/Kernel.php`:**

```php
protected function schedule(Schedule $schedule): void
{
    $schedule->command('ai:check-integrity')->daily();
}
```

---

## Configuration Updates

**Add to `.env`:**

```env
# AI Security Settings for Shared Hosting
AI_STRICT_MODE=true
AI_ENABLE_WHITELIST=true
AI_MAX_RETRIES=2
AI_ENABLE_WRITE_BLOCK=true

# Admin email for alerts
MAIL_ADMIN_ADDRESS=your-email@example.com
```

**Add to `config/ai.php` (create if not exists):**

```php
<?php

return [
    'strict_mode' => env('AI_STRICT_MODE', true),
    'enable_whitelist' => env('AI_ENABLE_WHITELIST', true),
    'max_retries' => env('AI_MAX_RETRIES', 2),
    'enable_write_block' => env('AI_ENABLE_WRITE_BLOCK', true),
    
    'security' => [
        'max_daily_requests_per_user' => 50,
        'query_timeout_seconds' => 10,
        'max_results_limit' => 100,
    ],
];
```

---

## Rate Limiting (Important)

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
        return Limit::perMinute(5)->by($request->user()->id);
    });
    
    // ...
}
```

---

## Deployment Checklist for Hostinger

- [ ] Create `app/Services/AiSqlParserService.php`
- [ ] Create `config/ai_queries.php` with your approved queries
- [ ] Update `app/Services/AiQueryPlannerService.php` with whitelist matching
- [ ] Update `app/Services/AiSqlValidatorService.php` to use parser
- [ ] Add write blocker to `AppServiceProvider.php`
- [ ] Create integrity check command
- [ ] Update `.env` with strict mode settings
- [ ] Set up daily cron job for integrity check
- [ ] Test thoroughly before going live

**To set up cron job in Hostinger:**

1. Go to Hosting → Manage → Advanced → Cron Jobs
2. Add new cron job:
   - Command: `php /home/yourusername/public_html/artisan ai:check-integrity`
   - Schedule: Once per day (0 0 * * *)

---

## How It Works

```
User Question
     │
     ▼
┌─────────────────┐
│  Match to       │
│  Whitelist?     │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
Yes │         │ No
    ▼         ▼
┌──────────┐  ┌──────────────────┐
│ Execute  │  │ Reject or        │
│ Template │  │ Restricted AI    │
│ (Safe)   │  │ (Limited)        │
└──────────┘  └──────────────────┘
                     │
                     ▼
              ┌──────────────┐
              │ SQL Parser   │
              │ Validation   │
              └──────┬───────┘
                     │
                ┌────┴────┐
                ▼         ▼
           Pass │         │ Fail
                ▼         ▼
           ┌────────┐  ┌────────┐
           │ Execute│  │ Block  │
           │ via    │  │ + Log  │
           │ Builder│  │        │
           └────────┘  └────────┘
```

---

## Security Summary

| Layer | Protection | Fallback |
|-------|-----------|----------|
| Whitelist | Only pre-approved queries run | Unknown queries rejected |
| SQL Parser | Syntax validation | Blocks malformed queries |
| Write Blocker | Runtime protection | Exception thrown on writes |
| Rate Limiting | 5 requests/minute | 429 error |
| Integrity Check | Daily monitoring | Email alerts |

**This setup gives you defense in depth even without database-level privileges.**
