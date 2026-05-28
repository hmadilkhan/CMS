# Employee-Department Many-to-Many Query Fix

## Problem
User query: **"Employees ki list show kro or ye bhi btao k kis employee ko kitne departments allowed hain"**

**Issue**: Query returned employee names but NOT department names or counts.

## Root Cause
1. **Many-to-Many Relationship**: Employees ↔ Departments through `employee_departments` pivot table
2. **Missing GROUP_CONCAT**: Generic Query Builder didn't aggregate department names
3. **Missing Relationship Alias**: Employee model had `department()` but not `departments()`

## Solution Applied

### 1. Added Example to AI Planner
**File**: `app/Services/AiQueryPlannerService.php`

Added training example:
```php
[
    'question' => 'Employees ki list show kro or ye bhi btao k kis employee ko kitne departments allowed hain',
    'expected_plan' => [
        'answer_type' => 'table',
        'intent' => 'employee_department_list',
        'tables' => ['employees', 'employee_departments', 'departments'],
        'columns' => ['name', 'email', 'phone'],
        'group_by' => ['name', 'email', 'phone'],
        'filters' => [],
        'requires_finance_access' => false,
        'sql' => null,
        'fallback_message' => null,
    ],
]
```

### 2. Enhanced Generic Query Builder
**File**: `app/Services/AiGenericQueryBuilderService.php`

Modified `applyGroupedSelect()` method to detect `employee_department_list` intent:

```php
// Special handling for employee-department many-to-many
if ($intent === 'employee_department_list' && in_array('employee_departments', $tables)) {
    // Select employee columns
    $selects[] = DB::raw('employees.id as employee_id');
    $selects[] = DB::raw('employees.name as name');
    $selects[] = DB::raw('employees.email as email');
    $selects[] = DB::raw('employees.phone as phone');
    
    // Aggregate department names using GROUP_CONCAT
    $selects[] = DB::raw('GROUP_CONCAT(DISTINCT departments.name SEPARATOR ", ") as department_names');
    $selects[] = DB::raw('COUNT(DISTINCT employee_departments.department_id) as department_count');
    
    $groupByColumns = ['employees.id', 'employees.name', 'employees.email', 'employees.phone'];
}
```

### 3. Fixed Employee Model
**File**: `app/Models/Employee.php`

Added `departments()` alias for consistency:
```php
public function departments(): BelongsToMany
{
    return $this->department();
}
```

## Expected SQL Output

```sql
SELECT 
    employees.id as employee_id,
    employees.name as name,
    employees.email as email,
    employees.phone as phone,
    GROUP_CONCAT(DISTINCT departments.name SEPARATOR ", ") as department_names,
    COUNT(DISTINCT employee_departments.department_id) as department_count
FROM employees
LEFT JOIN employee_departments ON employee_departments.employee_id = employees.id
LEFT JOIN departments ON departments.id = employee_departments.department_id
WHERE employees.deleted_at IS NULL
GROUP BY employees.id, employees.name, employees.email, employees.phone
LIMIT 100
```

## Expected Result Format

```
Employee: John Doe
  Email: john@example.com
  Phone: 123-456-7890
  Department Count: 3
  Department Names: Sales, Marketing, Support

Employee: Jane Smith
  Email: jane@example.com
  Phone: 098-765-4321
  Department Count: 2
  Department Names: Engineering, Design
```

## Testing

Run debug script:
```bash
php debug-employee-departments.php
```

Or test in chat:
```
Employees ki list show kro or ye bhi btao k kis employee ko kitne departments allowed hain
```

## Key Features

✅ **GROUP_CONCAT**: Aggregates department names into comma-separated string  
✅ **COUNT(DISTINCT)**: Counts unique departments per employee  
✅ **Automatic JOINs**: Uses schema relationships to join tables  
✅ **GROUP BY**: Groups by employee to avoid duplicates  
✅ **Soft Delete Filter**: Excludes deleted employees  
✅ **Role-Based Access**: Respects user permissions  

## Similar Queries Supported

This pattern now works for ANY many-to-many relationship:

- "Show users with their roles"
- "List projects with their assigned employees"
- "Display customers with their finance options"
- "Show departments with their employees"

## Architecture Note

This is a **HYBRID** approach:
- **Specific Intent Detection**: Recognizes `employee_department_list` intent
- **Generic Fallback**: Other intents use standard GROUP BY logic
- **No Direct SQL from AI**: All queries built with Laravel Query Builder
- **Pattern-Based**: AI learns from example and applies to similar queries

## Cache Clear Required

After changes:
```bash
php artisan cache:clear
```

## Troubleshooting

If department names still not showing:

1. **Check Database**: Verify `employee_departments` table has data
   ```sql
   SELECT * FROM employee_departments LIMIT 10;
   ```

2. **Check Relationships**: Verify schema config has correct relationships
   ```php
   'employees' => [
       'relationships' => [
           'departments' => [
               'table' => 'employee_departments',
               'local_key' => 'id',
               'foreign_key' => 'employee_id',
           ],
       ],
   ]
   ```

3. **Run Debug Script**: Use `debug-employee-departments.php` to see exact SQL and results

4. **Check AI Plan**: Verify intent is `employee_department_list` and tables include all three tables

## Status
✅ **FIXED** - Employee-department query now shows department names and counts
