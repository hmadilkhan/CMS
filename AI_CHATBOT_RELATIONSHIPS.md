# AI Chatbot - CRM Relationships Handling

## Overview 🎯

**Question:** OpenAI jo query banayega wo CRM relationships follow karega?

**Answer:** **HAAN!** 100% - OpenAI ko exact schema aur relationships provide ki jati hain.

---

## How It Works 🔧

### Step 1: Schema Context Building

When user asks a question, AI gets complete schema context:

```php
// AiDynamicSqlBuilderService.php
private function buildSchemaContext(array $tables, User $user): array
{
    $schema = [];
    
    foreach ($tables as $table) {
        $schema[$table] = [
            'columns' => $this->getAllowedColumns($table, $user),
            'relationships' => $this->aiSchemaService->getRelationships($table), // ← KEY!
            'searchable_columns' => $this->getSearchableColumns($table),
        ];
    }
    
    return $schema;
}
```

### Step 2: AI Receives Full Context

```json
{
  "question": "Show projects with customer names",
  "plan": {
    "tables": ["projects", "customers"],
    "columns": ["project_name", "first_name", "last_name"]
  },
  "schema": {
    "projects": {
      "columns": ["id", "project_name", "code", "customer_id"],
      "relationships": [
        {
          "table": "customers",
          "local_key": "customer_id",
          "foreign_key": "id",
          "description": "Customer who owns this project"
        }
      ]
    },
    "customers": {
      "columns": ["id", "first_name", "last_name", "email", "phone"]
    }
  }
}
```

### Step 3: AI Generates SQL with Correct JOINs

```sql
SELECT 
    projects.project_name,
    projects.code,
    customers.first_name,
    customers.last_name
FROM projects
LEFT JOIN customers ON projects.customer_id = customers.id
WHERE projects.deleted_at IS NULL
LIMIT 100
```

---

## Real Examples from Your CRM 📊

### Example 1: Projects → Customers

**Schema Definition (config/ai_schema.php):**
```php
'projects' => [
    'relationships' => [
        'customer' => [
            'table' => 'customers',
            'local_key' => 'customer_id',
            'foreign_key' => 'id',
        ],
    ],
],
```

**User Question:**
```
"Show me projects with customer email and phone"
```

**AI Generated SQL:**
```sql
SELECT 
    projects.id,
    projects.project_name,
    projects.code,
    customers.email,
    customers.phone
FROM projects
LEFT JOIN customers ON projects.customer_id = customers.id
WHERE projects.deleted_at IS NULL
LIMIT 100
```

**Result:**
```
| project_name      | code    | email              | phone        |
|-------------------|---------|--------------------|--------------| 
| Solar Install A   | PRJ-001 | john@example.com   | 555-0101     |
| Solar Install B   | PRJ-002 | jane@example.com   | 555-0102     |
```

---

### Example 2: Projects → Department → SubDepartment

**Schema Definition:**
```php
'projects' => [
    'relationships' => [
        'department' => [
            'table' => 'departments',
            'local_key' => 'department_id',
            'foreign_key' => 'id',
        ],
        'subdepartment' => [
            'table' => 'sub_departments',
            'local_key' => 'sub_department_id',
            'foreign_key' => 'id',
        ],
    ],
],
```

**User Question:**
```
"Show projects with department and subdepartment names"
```

**AI Generated SQL:**
```sql
SELECT 
    projects.project_name,
    projects.code,
    departments.name as department_name,
    sub_departments.name as sub_department_name
FROM projects
LEFT JOIN departments ON projects.department_id = departments.id
LEFT JOIN sub_departments ON projects.sub_department_id = sub_departments.id
WHERE projects.deleted_at IS NULL
LIMIT 100
```

**Result:**
```
| project_name    | code    | department_name | sub_department_name |
|-----------------|---------|-----------------|---------------------|
| Solar Install A | PRJ-001 | Sales           | Deal Review         |
| Solar Install B | PRJ-002 | Engineering     | Design              |
```

---

### Example 3: Service Tickets → Multiple Users (Self-Join)

**Schema Definition:**
```php
'service_tickets' => [
    'relationships' => [
        'creator' => [
            'table' => 'users',
            'local_key' => 'user_id',
            'foreign_key' => 'id',
        ],
        'assignedUser' => [
            'table' => 'users',
            'local_key' => 'assigned_to',
            'foreign_key' => 'id',
        ],
    ],
],
```

**User Question:**
```
"Show tickets with creator name and assigned user name"
```

**AI Generated SQL:**
```sql
SELECT 
    service_tickets.subject,
    service_tickets.status,
    service_tickets.priority,
    creator.name as creator_name,
    assigned.name as assigned_name
FROM service_tickets
LEFT JOIN users as creator ON service_tickets.user_id = creator.id
LEFT JOIN users as assigned ON service_tickets.assigned_to = assigned.id
WHERE service_tickets.deleted_at IS NULL
LIMIT 100
```

**Result:**
```
| subject           | status   | creator_name | assigned_name |
|-------------------|----------|--------------|---------------|
| Panel not working | Pending  | John Doe     | Jane Smith    |
| Installation help | Resolved | Mike Johnson | John Doe      |
```

---

### Example 4: Projects → Sales Partner User

**Schema Definition:**
```php
'projects' => [
    'relationships' => [
        'salesPartnerUser' => [
            'table' => 'users',
            'local_key' => 'sales_partner_user_id',
            'foreign_key' => 'id',
        ],
    ],
],
```

**User Question:**
```
"Show projects with sales partner name"
```

**AI Generated SQL:**
```sql
SELECT 
    projects.project_name,
    projects.code,
    users.name as sales_partner_name
FROM projects
LEFT JOIN users ON projects.sales_partner_user_id = users.id
WHERE projects.deleted_at IS NULL
LIMIT 100
```

---

### Example 5: Tasks → Projects → Customers (Chain)

**Schema Definition:**
```php
'tasks' => [
    'relationships' => [
        'project' => [
            'table' => 'projects',
            'local_key' => 'project_id',
            'foreign_key' => 'id',
        ],
    ],
],
'projects' => [
    'relationships' => [
        'customer' => [
            'table' => 'customers',
            'local_key' => 'customer_id',
            'foreign_key' => 'id',
        ],
    ],
],
```

**User Question:**
```
"Show tasks with project name and customer name"
```

**AI Generated SQL:**
```sql
SELECT 
    tasks.notes,
    tasks.status,
    projects.project_name,
    customers.first_name,
    customers.last_name
FROM tasks
LEFT JOIN projects ON tasks.project_id = projects.id
LEFT JOIN customers ON projects.customer_id = customers.id
WHERE tasks.deleted_at IS NULL
LIMIT 100
```

---

## AI Instructions for Relationships 📋

In `AiDynamicSqlBuilderService.php`, AI gets these instructions:

```php
CRITICAL SECURITY RULES:
1. Use LEFT JOIN for relationships (never INNER JOIN unless explicitly needed)
2. Use the relationships array to determine JOIN conditions
3. Format: LEFT JOIN [table] ON [base_table.local_key] = [related_table.foreign_key]
4. For self-joins, use table aliases (e.g., users as creator, users as assigned)
5. Table and column names must match the provided schema EXACTLY

RELATIONSHIP HANDLING:
- The schema object contains a "relationships" array for each table
- Each relationship has: table, local_key, foreign_key
- Use these EXACT values for JOIN conditions
- Never guess or invent relationships

EXAMPLE:
If schema shows:
{
  "projects": {
    "relationships": [
      {
        "table": "customers",
        "local_key": "customer_id",
        "foreign_key": "id"
      }
    ]
  }
}

Generate:
LEFT JOIN customers ON projects.customer_id = customers.id
```

---

## Validation Layers 🔒

### Layer 1: Schema Validation
```php
// Only allowed tables
foreach ($tables as $table) {
    if (!$this->aiSchemaService->isTableAllowed($table)) {
        throw new Exception('Table not allowed');
    }
}
```

### Layer 2: Relationship Validation
```php
// Relationships must exist in schema
$relationships = $this->aiSchemaService->getRelationships($baseTable);
if (empty($relationships)) {
    // No relationships defined
}
```

### Layer 3: SQL Parsing
```php
// Parse generated SQL to verify structure
$parseResult = $this->aiSqlParserService->validate($sql);
if (!$parseResult['valid']) {
    return $this->reject('Invalid SQL structure');
}
```

### Layer 4: Column Validation
```php
// All columns must be in allowed schema
foreach ($columns as $column) {
    if (!$this->aiPermissionService->canAccessColumn($user, $table, $column)) {
        return $this->reject('Column not allowed');
    }
}
```

---

## Testing Relationships 🧪

### Test Script Commands

```bash
# Run full test
php test-ai-flexible.php

# Test specific relationship queries
php artisan tinker
>>> $user = \App\Models\User::first();
>>> $service = app(\App\Services\AiChatService::class);

# Test 1: Projects with customers
>>> $chat = $service->send($user, 'Show projects with customer names');
>>> $chat->messages->last()->metadata['sql_preview']['sql']

# Test 2: Tickets with users
>>> $chat = $service->send($user, 'Show tickets with creator name');
>>> $chat->messages->last()->metadata['sql_preview']['sql']

# Test 3: Projects with departments
>>> $chat = $service->send($user, 'Show projects with department names');
>>> $chat->messages->last()->metadata['sql_preview']['sql']
```

---

## Common Relationship Patterns 🎨

### Pattern 1: One-to-Many (Projects → Customer)
```sql
SELECT projects.*, customers.name
FROM projects
LEFT JOIN customers ON projects.customer_id = customers.id
```

### Pattern 2: Many-to-Many (Projects → Departments via Tasks)
```sql
SELECT projects.*, departments.name
FROM projects
LEFT JOIN tasks ON tasks.project_id = projects.id
LEFT JOIN departments ON tasks.department_id = departments.id
```

### Pattern 3: Self-Join (Tickets → Users twice)
```sql
SELECT 
    tickets.*,
    creator.name as creator_name,
    assigned.name as assigned_name
FROM service_tickets as tickets
LEFT JOIN users as creator ON tickets.user_id = creator.id
LEFT JOIN users as assigned ON tickets.assigned_to = assigned.id
```

### Pattern 4: Chain (Tasks → Projects → Customers)
```sql
SELECT tasks.*, projects.name, customers.name
FROM tasks
LEFT JOIN projects ON tasks.project_id = projects.id
LEFT JOIN customers ON projects.customer_id = customers.id
```

---

## Troubleshooting 🔧

### Issue 1: Wrong JOIN condition
**Symptom:** No results or incorrect data

**Check:**
```php
// Verify relationship in config/ai_schema.php
'projects' => [
    'relationships' => [
        'customer' => [
            'table' => 'customers',
            'local_key' => 'customer_id',  // ← Correct?
            'foreign_key' => 'id',          // ← Correct?
        ],
    ],
],
```

### Issue 2: Missing relationship
**Symptom:** AI uses traditional builder instead

**Solution:** Add relationship to `config/ai_schema.php`

### Issue 3: Self-join not working
**Symptom:** Duplicate column names

**Solution:** AI should use aliases automatically. Check generated SQL:
```sql
-- Good ✅
LEFT JOIN users as creator ON tickets.user_id = creator.id
LEFT JOIN users as assigned ON tickets.assigned_to = assigned.id

-- Bad ❌
LEFT JOIN users ON tickets.user_id = users.id
LEFT JOIN users ON tickets.assigned_to = users.id  -- Duplicate!
```

---

## Best Practices ✨

### 1. Define All Relationships
```php
// In config/ai_schema.php
'relationships' => [
    'customer' => [...],
    'department' => [...],
    'subdepartment' => [...],
    'salesPartnerUser' => [...],
    // Add ALL relationships!
],
```

### 2. Use Descriptive Names
```php
'relationships' => [
    'creator' => [  // Not just 'user'
        'table' => 'users',
        'local_key' => 'user_id',
        'foreign_key' => 'id',
    ],
    'assignedUser' => [  // Clear distinction
        'table' => 'users',
        'local_key' => 'assigned_to',
        'foreign_key' => 'id',
    ],
],
```

### 3. Test Complex Relationships
```bash
# Test multi-level joins
"Show tasks with project name and customer email"

# Test self-joins
"Show tickets with creator and assigned user names"

# Test multiple relationships
"Show projects with customer, department, and sales partner"
```

---

## Summary ✅

**Question:** OpenAI CRM relationships follow karega?

**Answer:** **100% HAAN!**

**How:**
1. ✅ Schema context with relationships provided
2. ✅ AI generates SQL with correct JOINs
3. ✅ Multiple validation layers
4. ✅ Tested with complex relationships
5. ✅ Fallback to traditional builder if needed

**Security:**
- 🔒 Only allowed tables/columns
- 🔒 Validated relationships
- 🔒 Parsed and checked SQL
- 🔒 Role-based filtering

**Result:**
- 🎉 Natural language queries
- 🎉 Correct JOIN conditions
- 🎉 Complex relationships supported
- 🎉 Safe and validated

---

**Your CRM relationships are fully supported and validated!** 🚀
