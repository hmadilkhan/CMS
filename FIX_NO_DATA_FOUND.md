# Fix: "No data found for this request"

## Problem 🔴

Query: **"How many projects assigned to me?"**
Result: **"No data found for this request."**

---

## Quick Diagnosis 🔍

Run this debug script:
```bash
php debug-my-projects.php
```

This will show:
1. ✅ User details and roles
2. ✅ How many projects in database
3. ✅ How many projects assigned to user
4. ✅ Generated SQL query
5. ✅ Why no results

---

## Common Causes & Solutions 💡

### Cause 1: No Projects Assigned to User ❌

**Check:**
```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $employee = \App\Models\Employee::where('user_id', $user->id)->first();
>>> \App\Models\Task::where('employee_id', $employee->id)->count();
```

**Solution:** Assign projects to user
```sql
-- Find a project
SELECT id FROM projects LIMIT 1;

-- Find employee
SELECT id FROM employees WHERE user_id = YOUR_USER_ID;

-- Create task assignment
INSERT INTO tasks (project_id, employee_id, status, created_at, updated_at)
VALUES (PROJECT_ID, EMPLOYEE_ID, 'In-Progress', NOW(), NOW());
```

---

### Cause 2: User is Admin (Sees All Projects) ✅

**Check:**
```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $user->hasAnyRole(['Super Admin', 'Admin']);
```

**If TRUE:** Admin sees ALL projects, not just assigned ones.

**Test with different query:**
```
"How many projects are there?"  # Should show total
```

---

### Cause 3: Wrong Role-Based Filter ❌

**Check user role:**
```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $user->roles->pluck('name');
```

**Expected filters by role:**
- **Employee** → Filter by tasks.employee_id
- **Sales Person** → Filter by sales_partner_user_id
- **Sub-Contractor** → Filter by sub_contractor_user_id
- **Manager** → Filter by department_id
- **Admin** → No filter (sees all)

---

### Cause 4: AI Not Detecting "My/Assigned" ❌

**Check generated plan:**
```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $service = app(\App\Services\AiChatService::class);
>>> $chat = $service->send($user, 'How many projects assigned to me?');
>>> $chat->messages->last()->metadata['query_plan']['filters']
```

**Expected filter:**
```php
[
    [
        'column' => 'user_id',
        'operator' => '=',
        'value' => 'current_user.id'
    ]
]
```

**If missing:** AI didn't detect "assigned to me"

**Solution:** Clear cache and retry
```bash
php artisan cache:clear
php artisan config:clear
```

---

### Cause 5: Database Empty ❌

**Check:**
```bash
php artisan tinker
>>> \App\Models\Project::count();
>>> \App\Models\Task::count();
>>> \App\Models\Employee::count();
```

**If 0:** Add test data
```bash
php artisan db:seed  # If you have seeders
```

Or manually:
```sql
-- Add test project
INSERT INTO projects (project_name, code, created_at, updated_at)
VALUES ('Test Project', 'TEST-001', NOW(), NOW());

-- Add employee
INSERT INTO employees (name, user_id, created_at, updated_at)
VALUES ('Test Employee', YOUR_USER_ID, NOW(), NOW());

-- Add task assignment
INSERT INTO tasks (project_id, employee_id, status, created_at, updated_at)
VALUES (LAST_INSERT_ID(), EMPLOYEE_ID, 'In-Progress', NOW(), NOW());
```

---

## Step-by-Step Fix 🛠️

### Step 1: Run Debug Script
```bash
php debug-my-projects.php
```

### Step 2: Check Output
Look for:
- ✅ "Projects assigned to this employee: X" (should be > 0)
- ✅ Generated SQL includes user filter
- ✅ Status: success

### Step 3: If Still Failing

**Option A: Test with Admin User**
```bash
php artisan tinker
>>> $admin = \App\Models\User::whereHas('roles', function($q) {
    $q->where('name', 'Super Admin');
})->first();
>>> $service = app(\App\Services\AiChatService::class);
>>> $chat = $service->send($admin, 'How many projects?');
>>> $chat->messages->last()->content
```

**Option B: Test Direct Query**
```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $employee = \App\Models\Employee::where('user_id', $user->id)->first();
>>> \App\Models\Project::whereHas('tasks', function($q) use ($employee) {
    $q->where('employee_id', $employee->id);
})->count();
```

---

## Expected SQL Queries 📋

### For Employee Role:
```sql
SELECT COUNT(*) as aggregate
FROM projects
LEFT JOIN tasks ON tasks.project_id = projects.id
LEFT JOIN employees ON tasks.employee_id = employees.id
WHERE employees.user_id = ?
AND projects.deleted_at IS NULL
LIMIT 100
```

### For Sales Person:
```sql
SELECT COUNT(*) as aggregate
FROM projects
WHERE projects.sales_partner_user_id = ?
AND projects.deleted_at IS NULL
LIMIT 100
```

### For Admin:
```sql
SELECT COUNT(*) as aggregate
FROM projects
WHERE projects.deleted_at IS NULL
LIMIT 100
```

---

## Test Different Queries 🧪

Try these variations:

### 1. Generic Count (Should Work)
```
"How many projects are there?"
```

### 2. All Projects List (Should Work)
```
"Show all projects"
```

### 3. Specific Filter (Should Work)
```
"Show projects in Sales department"
```

### 4. My Projects (Needs Assignment)
```
"How many projects assigned to me?"
"My projects"
"Show my projects"
```

---

## Quick Fixes ⚡

### Fix 1: Clear All Caches
```bash
php artisan cache:clear
php artisan config:clear
php artisan view:clear
composer dump-autoload
```

### Fix 2: Check OpenAI API
```bash
php artisan tinker
>>> config('services.openai.api_key')  # Should return valid key
```

### Fix 3: Check Logs
```bash
tail -f storage/logs/laravel.log
```

Look for errors related to:
- OpenAI API
- SQL execution
- Permission denied

### Fix 4: Test Traditional Builder
If AI-generated SQL fails, system should fallback to traditional builder.

Check:
```bash
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $service = app(\App\Services\AiChatService::class);
>>> $chat = $service->send($user, 'Total projects count');
>>> $chat->messages->last()->metadata['sql_preview']['ai_generated'] ?? false
```

If `false`, traditional builder was used (good fallback).

---

## Create Test Data 📊

If database is empty:

```php
// Run in tinker: php artisan tinker

// Create user
$user = \App\Models\User::create([
    'name' => 'Test User',
    'email' => 'test@example.com',
    'password' => bcrypt('password'),
]);

// Assign role
$user->assignRole('Employee');

// Create employee
$employee = \App\Models\Employee::create([
    'name' => 'Test Employee',
    'user_id' => $user->id,
    'code' => 'EMP001',
]);

// Create customer
$customer = \App\Models\Customer::create([
    'first_name' => 'John',
    'last_name' => 'Doe',
    'email' => 'john@example.com',
    'phone' => '555-0100',
]);

// Create project
$project = \App\Models\Project::create([
    'project_name' => 'Test Solar Project',
    'code' => 'PRJ-001',
    'customer_id' => $customer->id,
    'department_id' => 1,
]);

// Create task (assigns project to employee)
$task = \App\Models\Task::create([
    'project_id' => $project->id,
    'employee_id' => $employee->id,
    'status' => 'In-Progress',
]);

// Test query
$service = app(\App\Services\AiChatService::class);
$chat = $service->send($user, 'How many projects assigned to me?');
echo $chat->messages->last()->content;
```

---

## Success Indicators ✅

You're good when:
- ✅ Debug script shows assigned projects > 0
- ✅ Generated SQL includes user filter
- ✅ Query returns count > 0
- ✅ Response shows actual number

---

## Still Not Working? 🆘

1. **Check logs:**
   ```bash
   tail -f storage/logs/laravel.log
   ```

2. **Check query logs:**
   ```sql
   SELECT * FROM ai_query_logs 
   WHERE status = 'failed' 
   ORDER BY created_at DESC 
   LIMIT 5;
   ```

3. **Test with raw SQL:**
   ```bash
   php artisan tinker
   >>> DB::select("SELECT COUNT(*) as count FROM projects WHERE deleted_at IS NULL");
   ```

4. **Verify relationships:**
   ```bash
   php artisan tinker
   >>> \App\Models\Project::first()->tasks;
   >>> \App\Models\Task::first()->employee;
   >>> \App\Models\Employee::first()->user;
   ```

---

## Summary 📝

**Most Common Issue:** User has no assigned projects in database.

**Quick Fix:** 
1. Run `php debug-my-projects.php`
2. Check if projects are assigned
3. If not, assign test projects
4. Retry query

**Expected Result:**
```
"You have 5 projects assigned to you."
```

---

**Need more help?** Share the output of `php debug-my-projects.php`
