# AI Query Planner

The CRM AI chat uses a hybrid planning flow:

`User Message -> AI Query Planner -> Schema/Permission Validator -> Entity Resolver -> Safe Query Builder -> Result Formatter`

OpenAI must only return structured JSON plans. It must never return raw SQL for execution. Laravel validates the plan, resolves CRM entity names, builds the database query with Query Builder, validates the final SELECT preview, executes it, and then sends only result rows to the formatter.

## Adding A Table Or Module

Add the table to `config/ai_schema.php` under `tables`.

Each mapping should include:

- `model`: model class or `null` for a placeholder table.
- `table`: real database table name.
- `allowed_columns`: only columns AI may access.
- `searchable_columns`: columns safe for text/entity matching.
- `relationships`: joins AI may request.
- `sensitive_columns`: finance, profitability, salary, token, credential, or private columns.
- `default_filters`: optional filters applied by the safe builder. If omitted and `deleted_at` is allowed, the schema service applies `deleted_at is null`.
- `default_sort_column`: preferred sort.
- `access_rule`: one of `project_access`, `customer_access`, `ticket_access`, `department_access`, `user_access`, `finance_access`, `profitability_access`, or `admin_only`.

Never add password, remember token, API token, secret, or credential columns to `allowed_columns`.

## Adding Relationships

Relationships are allowlisted in the table config:

```php
'customer' => [
    'table' => 'customers',
    'local_key' => 'customer_id',
    'foreign_key' => 'id',
],
```

The plan validator rejects unknown joins. The safe builder only joins relationships that exist in `ai_schema.php`.

## Sensitive Columns

Put finance/profitability/private fields in `sensitive_columns`.

Finance/profitability columns require finance/admin permissions. Sensitive credential columns should not be present in `allowed_columns` at all.

## Access Rules

`AiAccessPolicyService` centralizes role access and delegates to the existing CRM permission service:

- `Super Admin` and `Admin`: all allowed CRM data.
- `Finance`: finance/profitability data plus CRM context allowed by schema.
- `Manager`: department/team data.
- `Employee`: assigned projects/tasks/tickets.

All queries still receive role filters in the safe builder before execution.

## Clarification And Fallback

If OpenAI confidence is below `0.65`, the plan is rejected and the user receives a clarification/fallback message.

`AiEntityResolverService` resolves names like customer, project, department, and user names. If multiple records match, the chat asks the user to choose one. If no record matches, the chat returns a friendly not-found message.

## Security Rules

- Never execute OpenAI SQL directly.
- Always validate plans against `config/ai_schema.php`.
- Always apply role filters.
- Always use SELECT-only queries.
- Always enforce `LIMIT 100`.
- Do not expose internal prompts or schema details to users.
- Do not log sensitive result data.
